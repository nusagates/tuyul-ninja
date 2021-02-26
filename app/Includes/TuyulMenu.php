<?php

namespace App\Includes;

class TuyulMenu
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'set_menu']);
    }

    /**
     * create admin page menu
     */
    public function set_menu()
    {
        add_menu_page("Tuyul Ninja", "Tuyul Ninja", 'manage_options', 'wpty-setting', [$this, 'job_page']);
    }

    /**
     * Display the option page fo the plugin
     */
    public function job_page()
    {
        $this->add_dependencies();
        $categories = get_categories();
        $schedules = wp_get_schedules();
        ?>
        <div id="app">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <h3><?php _e("Add Job to Tuyul", 'tuyul-ninja'); ?></h3>
                        <div class="form-group">
                            <label for="name"><?php _e("Job Name", 'tuyul-ninja'); ?></label>
                            <input type="text" v-model="field.name" id="name" class="form-control"/>
                        </div>
                        <div class="form-group">
                            <label for="job-list"><?php _e("Select Provider", 'tuyul-ninja'); ?></label>
                            <select v-model="field.provider" id="job-list" class="form-control">
                                <option value="email">Email</option>
                                <option value="blogger">Blogger (Publish via Email)</option>
                                <option value="telegram-channel"><?php _e("Telegram Channel", 'tuyul-ninja'); ?></option>
                            </select>
                        </div>
                        <form id="form-tb-setting" action="options.php" method="post">
                            <?php get_option('options'); ?>
                            <?php settings_fields('wpty_options'); ?>
                            <input type="hidden" name="action" value="wpty_save_general_setting">
                            <div v-show="field.provider==='email'||field.provider==='blogger'" class="form-group">
                                <label for="email"><?php _e("Email", 'tuyul-ninja'); ?></label>
                                <input v-model="field.email" required type="email"
                                       class="form-control"
                                       id="email"
                                       name="email">
                            </div>
                            <div v-show="field.provider==='telegram-channel'">
                                <div class="form-group">
                                    <label>Telegram Bot API Token</label>
                                    <input v-model="field.telegram_bot_token" type="text" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Telegram Channel Username</label>
                                    <input v-model="field.telegram_channel_username" type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="category"><?php _e("Category", 'tuyul-ninja'); ?></label>
                                <select v-model="field.category" id="category" name="category" class="form-control">
                                    <option value="all"><?php _e("All"); ?></option>
                                    <?php

                                    foreach ($categories as $item) {
                                        echo "<option value='$item->term_id'>$item->name</option>";
                                    }
                                    ?>
                                </select>
                                <small><?php _e("tell tuyul to choose which post category to send ", 'tuyul-ninja'); ?></small>
                            </div>
                            <div class="form-group">
                                <label for="priority"><?php _e("Priority", 'tuyul-ninja'); ?></label>
                                <select v-model="field.priority" id="priority" name="priority" class="form-control">
                                    <option value="random">
                                        <?php _e("Random", 'tuyul-ninja'); ?>
                                    </option>
                                    <option value="desc"><?php _e("Newer Post First", 'tuyul-ninja'); ?></option>
                                    <option value="asc"><?php _e("Older Post First", 'tuyul-ninja'); ?></option>
                                </select>
                                <small><?php _e("tell tuyul to choose which post to send first", 'tuyul-ninja'); ?></small>
                            </div>
                            <div class="form-group">
                                <label for="schedule"><?php _e("Send Schedule", 'tuyul-ninja'); ?></label>
                                <select v-model="field.schedule" id="schedule" class="form-control" name="schedule">
                                    <?php
                                    foreach ($schedules as $key => $schedule) {
                                        echo '<option value="' . $key . '">' . $schedule['display'] . '</option>';
                                    }
                                    ?>
                                </select>
                                <small><?php _e("how often the post will be sent by Tuyul", "tuyul-ninja"); ?></small>
                            </div>
                            <div class="form-group">
                                <label>Send Post Content as:</label><br>
                                <input value="full" type="radio" id="full" name="is_full" v-model="field.content_type"
                                       checked>
                                <label for="full">Full Content</label><br>
                                <input value="excerpt" type="radio" id="is_full2" name="is_full"
                                       v-model="field.content_type">
                                <label for="is_full2">Excerpt</label><br>
                            </div>
                            <div class="form-group">
                                <button :disabled="processing" @click.prevent="saveJob"
                                        class="btn btn-success w-100">
                                    <span v-if="processing"><?php _e('saving...', 'tuyul-ninja') ?></span>
                                    <span v-else><?php _e('Add Job', 'Tuyul Ninja'); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <h3><?php _e('Existing Job', 'tuyul-ninja') ?></h3>
                        <div class="table-responsive">
                            <table class="table table-striped w-100">
                                <thead>
                                <tr>
                                    <th><?php _e('Name', 'tuyul-ninja') ?></th>
                                    <th><?php _e('Provider', 'tuyul-ninja') ?></th>
                                    <th><?php _e('Category', 'tuyul-ninja') ?></th>
                                    <th><?php _e('Meta', 'tuyul-ninja') ?></th>
                                    <th><?php _e('Action', 'tuyul-ninja') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-if="jobs.length>0" v-for="(item, index) of jobs">
                                    <td class="align-middle" v-html="item.meta.data.name"/>
                                    <td class="align-middle" v-html="item.meta.data.provider"/>
                                    <td class="align-middle" v-html="item.meta.data.category_name"/>
                                    <td class="align-middle">
                                        <ol style="list-style: none">
                                            <li v-if="item.meta.data.provider==='email'"><?php _e('Email', 'tuyul-ninja') ?>
                                                :
                                                {{item.meta.data.email}}
                                            </li>
                                            <li v-if="item.meta.data.provider==='telegram-channel'"><?php _e('Channel', 'tuyul-ninja') ?>
                                                :
                                                {{item.meta.data.telegram_channel_username}}
                                            </li>
                                            <li><?php _e('Schedule', 'tuyul-ninja') ?>: {{item.schedule}}
                                            </li>
                                            <li v-if="item.meta.data.priority==='desc'"><?php _e('Priority: Newer first', 'tuyul-ninja') ?>
                                            </li>
                                            <li v-if="item.meta.data.priority==='asc'"><?php _e('Priority: Older first', 'tuyul-ninja') ?></li>
                                            <li v-if="item.meta.data.priority==='random'"><?php _e('Priority: Random', 'tuyul-ninja') ?></li>
                                        </ol>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button @click="runJob(item)"
                                                class="btn btn-success btn-sm mb-1"><?php _e('Run', 'tuyul-ninja') ?></button>
                                        <button @click="deleteJob(item)"
                                                class="btn btn-danger btn-sm mb-1"><?php _e('Delete', 'tuyul-ninja') ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="jobs.length<1">
                                    <td colspan="5"
                                        class="text-center"><?php _e("No job available", 'tuyul-digital'); ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <hr/>
                        <h3><?php _e('History', 'tuyul-ninja') ?></h3>
                        <div class="d-flex justify-content-between">
                            <div class="input-group mb-3 w-25">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Show</span>
                                </div>
                                <select @change="getData" v-model="limit" class="form-control">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="input-group-text">entries</span>
                            </div>
                            <div class="input-group mb-3 w-25">
                                <span class="input-group-text">Search</span>
                                <input @keyup="getData" v-model="search_term"
                                       placeholder="<?php _e('Serach by post title', 'tuyul-ninja') ?>" type="search"
                                       class="form-control"/>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="table-history" class="table table-striped">
                                <thead>
                                <tr>
                                    <th><?php _e('Post Title', 'tuyul-ninja') ?></th>
                                    <th><?php _e('Job', 'tuyul-ninja') ?></th>
                                    <th><?php _e('Sent', 'tuyul-ninja') ?></th>
                                    <th><?php _e('Action', 'tuyul-ninja') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-if="history.length>0" v-for="(item, index) of history">
                                    <td v-html="item.post_title"/>
                                    <td v-html="item.name"/>
                                    <td v-html="item.created_at"/>
                                    <td>
                                        <button @click="deleteHistory(item)"
                                                class="btn btn-sm btn-danger"><?php _e('Delete', 'tuyul-ninja') ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="history.length<1">
                                    <td colspan="4"
                                        class="text-center"><?php _e('No execution histories', 'tuyul-ninja') ?></td>
                                </tr>
                                </tbody>
                            </table>
                            <?php _('Run', 'tuyul-ninja') ?>
                        </div>
                        <div v-if="history_page.total_pages>1" class="d-flex justify-content-between">
                            <div>
                                Showing {{history_page.start_entry}} to {{history_page.end_entry}} of
                                {{history_page.total_entry}} entries
                            </div>
                            <ul class="pagination">
                                <li class="page-item" v-bind:class="{disabled:history_page.prev_page===false}">
                                    <a @click.prevent="getData(history_page.prev_page)" class="page-link" href="#"
                                       aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                </li>
                                <li class="page-item" v-bind:class="{disabled:history_page.next_page===false}">
                                    <a @click.prevent="getData(history_page.next_page)" class="page-link" href="#"
                                       aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Adding bootstrap and sweetalert to the page
     */
    private function add_dependencies()
    {
        // JS
        wp_register_script('wpty_bootstrap', TUYUL_URL . 'resources/js/bootstrap.min.js');
        wp_register_script('wpty_swal2', TUYUL_URL . 'resources/js/sweetalert2.js');
        wp_register_script('wpty_vue', TUYUL_URL . 'resources/js/vue.js');
        wp_register_script('wpty_axios', TUYUL_URL . 'resources/js/axios.min.js');
        wp_register_script('wpty_functions', TUYUL_URL . 'resources/js/functions.js');
        wp_enqueue_script('wpty_bootstrap');
        wp_enqueue_script('wpty_swal2');
        wp_enqueue_script('wpty_vue');
        wp_enqueue_script('wpty_axios');
        wp_enqueue_script('wpty_functions');

        // CSS
        wp_register_style('wpty_bootstrap', TUYUL_URL . 'resources/css/bootstrap.min.css');
        wp_enqueue_style('wpty_bootstrap');
    }
}