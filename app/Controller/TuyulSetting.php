<?php

namespace App\Controller;

class TuyulSetting
{
    private $DB;

    public function __construct()
    {
        global $wpdb;
        $this->DB = $wpdb;
        add_action('wp_ajax_wpty_save_general_setting', [$this, 'save_general_setting']);
        add_action('wp_ajax_wpty_get_processed_post', [$this, 'get_processed_post']);
        add_action('wp_ajax_wpty_save_job', [$this, 'save_job']);
        add_action('wp_ajax_wpty_get_job', [$this, 'get_job']);
        add_action('wp_ajax_wpty_run_job', [$this, 'run_job']);
        add_action('wp_ajax_wpty_delete_job', [$this, 'delete_job']);
        add_action('wp_ajax_wpty_delete_history', [$this, 'delete_history']);
    }

    /**
     * Creates a cronjob to send the post to the provider the user chooses
     */
    public function save_job()
    {
        $job_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : '';
        $schedule = isset($_POST['schedule']) ? sanitize_text_field($_POST['schedule']) : '';
        $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : 'full';
        $telegram_bot_token = isset($_POST['telegram_bot_token']) ? sanitize_text_field($_POST['telegram_bot_token']) : '';
        $telegram_channel_username = isset($_POST['telegram_channel_username']) ? sanitize_text_field($_POST['telegram_channel_username']) : '';
        if (empty($job_name)) die("Job name is required");
        if (empty($provider)) die("Please select provider");
        if (empty($category)) die("Please select category");
        if (empty($priority)) die("Please select priority");
        if (empty($schedule)) die("Please select schedule");

        $data = [
            'email' => $email,
            'category_id' => $category,
            'category_name' => $category === 'all' ? 'All' : get_the_category_by_ID($category),
            'priority' => $priority,
            'schedule' => $schedule,
            'name' => $job_name,
            'provider' => $provider,
            'content_type' => $content_type,
        ];
        switch ($provider) {
            case 'email':
                if (empty($email)) die("Email is required");
                if (!is_email($email)) die("Email format not valid");
                $data['email'] = $email;
                break;
            case 'telegram-channel':
                if (empty($telegram_bot_token)) die("Bot Token is required");
                if (empty($telegram_channel_username)) die("Channel username is required");
                $data['telegram_bot_token'] = $telegram_bot_token;
                $data['telegram_channel_username'] = '@' . $telegram_channel_username;
        }
        $data = json_encode($data);
        $this->DB->insert(TUYUL_TABLE_JOBS, array(
            'name' => $job_name,
            'provider' => $provider,
            'meta' => $data,
        ));
        $time = strtotime(date('Y-m-d H') . ':00:00');
        $data = json_decode($data, true);
        wp_schedule_event($time, $schedule, 'send_post_to_blogger', ['job_id' => $this->DB->insert_id, 'data' => $data]);
        echo 1;
        die(1);
    }

    /**
     * Execute jobs directly by user action without waiting for cron
     * @return bool boolean of job execution status whether successful or not
     */
    public function run_job()
    {
        $crons = _get_cron_array();
        $hookname = sanitize_text_field($_POST['hook']);
        $sig = sanitize_text_field($_POST['sig']);
        foreach ($crons as $time => $cron) {
            if (isset($cron[$hookname][$sig])) {
                $event = $cron[$hookname][$sig];

                $event['hook'] = $hookname;
                $event['timestamp'] = $time;

                $event = (object)$event;

                delete_transient('doing_cron');
                $scheduled = $this->force_schedule_single_event($hookname, $event->args); // UTC

                if (false === $scheduled) {
                    return $scheduled;
                }

                add_filter('cron_request', function (array $cron_request_array) {
                    $cron_request_array['url'] = add_query_arg('crontrol-single-event', 1, $cron_request_array['url']);
                    return $cron_request_array;
                });

                spawn_cron();

                sleep(1);

                /**
                 * Fires after a cron event is ran manually.
                 *
                 * @param object $event {
                 *     An object containing the event's data.
                 *
                 * @type string $hook Action hook to execute when the event is run.
                 * @type int $timestamp Unix timestamp (UTC) for when to next run the event.
                 * @type string|false $schedule How often the event should subsequently recur.
                 * @type array $args Array containing each separate argument to pass to the hook's callback function.
                 * @type int $interval The interval time in seconds for the schedule. Only present for recurring events.
                 * }
                 */
                do_action('crontrol/ran_event', $event);

                echo 1;
                die();
            }
        }


        echo "Unable to run this job";
        die();
    }

    /**
     * Get all available jobs created by user
     * @return array Array of job created and its execution history
     */
    public function get_job()
    {
        //get avaliable cron
        $crons = _get_cron_array();
        $events = array();

        if (empty($crons)) {
            return array();
        }

        foreach ($crons as $time => $cron) {
            foreach ($cron as $hook => $dings) {
                // get cron created by Tuyul Ninja Only
                if ($hook === 'send_post_to_blogger') {
                    foreach ($dings as $sig => $data) {

                        // This is a prime candidate for a Crontrol_Event class but I'm not bothering currently.
                        $events[] = (object)array(
                            'hook' => $hook,
                            'time' => $time, // UTC
                            'sig' => $sig,
                            'meta' => $data['args'],
                            'schedule' => $data['schedule'],
                            'interval' => isset($data['interval']) ? $data['interval'] : null,
                        );

                    }
                }
            }
        }

        //get all job execution history
        $page_limit = isset($_POST['limit']) ? (int)sanitize_text_field($_POST["limit"]) : 20;
        $page = isset($_POST['page']) ? (int)sanitize_text_field($_POST["page"]) : 1;
        $term = isset($_POST['term']) ? sanitize_text_field($_POST["term"]) : '';
        $search = empty($term) ? '' : " and p.post_title like '%$term%'";

        $start_page = ($page > 1) ? ($page * $page_limit) - $page_limit : 0;
        $history = $this->DB->get_results('select h.id, p.post_title, c.name, h.created_at  
        from ' . $this->DB->posts . ' p 
        left join ' . TUYUL_TABLE_HISTORIES . ' h on p.ID=h.post_id 
        left join ' . TUYUL_TABLE_JOBS . ' c on c.id=h.job_id 
        where p.ID in (select post_id from ' . TUYUL_TABLE_HISTORIES . ') ' . $search . ' limit ' . $start_page . ',' . $page_limit . ' ');
        $all_history = $this->DB->get_results('select h.id, p.post_title, c.name, h.created_at  
        from ' . $this->DB->posts . ' p 
        left join ' . TUYUL_TABLE_HISTORIES . ' h on p.ID=h.post_id 
        left join ' . TUYUL_TABLE_JOBS . ' c on c.id=h.job_id 
        where p.ID in (select post_id from ' . TUYUL_TABLE_HISTORIES . ')');
        $pages = ceil(count($all_history) / $page_limit);
        $history_data = [
            'data' => $history,
            'button' => [
                'limit' => $page_limit,
                'current_page' => $page,
                'max_entry' => $page * $page_limit,
                'total_pages' => $pages,
                'start_entry' => ($page > 1) ? ($page * $page_limit) - $page_limit + 1 : 1,
                'end_entry' => ($page < $pages) ? ($page * $page_limit) : count($all_history),
                'total_entry' => count($all_history),
                'prev_page' => $page <= 1 ? false : $page - 1,
                'next_page' => $page < $pages ? $page + 1 : false,
            ]
        ];
        wp_send_json(['events' => $events, 'history' => $history_data]);
    }

    /**
     * Delete specific job triggered by user event from admin page
     */
    public function delete_job()
    {
        $meta = json_encode(json_decode(stripslashes($_POST['meta'])));
        $meta = json_decode($meta, true);
        foreach ($meta['data'] as $key => $item) {
            $meta['data'][sanitize_text_field($key)] = sanitize_text_field($item);
        }
        $meta['job_id'] = (int)$meta['job_id'];

        $job_id = $meta['job_id'];
        $timestamp = sanitize_text_field($_POST['timestamp']);
        $unscheduled = wp_unschedule_event($timestamp, 'send_post_to_blogger', $meta);
        if (!$unscheduled) {
            echo "Unable to destroy schedule. please try again";
            die();
        }
        /**
         * Delete job selected by user and its execution history from database
         */
        $this->DB->get_results("DELETE FROM " . TUYUL_TABLE_JOBS . " WHERE id=" . $job_id);
        $this->DB->get_results("DELETE FROM " . TUYUL_TABLE_HISTORIES . " WHERE job_id=" . $job_id);
        echo 1;
        die();
    }

    /**
     * Execute deletion of job execution history by user from admin page
     */
    public function delete_history()
    {
        $history_id = (int)sanitize_text_field($_POST['history_id']);
        $this->DB->get_results("delete from " . TUYUL_TABLE_HISTORIES . " where id=" . $history_id);
        echo 1;
        die();
    }

    /**
     * Forcibly schedules a single event for the purpose of manually running it.
     *
     * This is used instead of `wp_schedule_single_event()` to avoid the duplicate check that's otherwise performed.
     *
     * @param string $hook Action hook to execute when the event is run.
     * @param array $args Optional. Array containing each separate argument to pass to the hook's callback function.
     * @return bool True if event successfully scheduled. False for failure.
     */
    private function force_schedule_single_event($hook, $args = array())
    {
        $event = (object)array(
            'hook' => $hook,
            'timestamp' => 1,
            'schedule' => false,
            'args' => $args,
        );
        $crons = (array)_get_cron_array();
        $key = md5(serialize($event->args));

        $crons[$event->timestamp][$event->hook][$key] = array(
            'schedule' => $event->schedule,
            'args' => $event->args,
        );
        uksort($crons, 'strnatcasecmp');

        return _set_cron_array($crons);
    }
}