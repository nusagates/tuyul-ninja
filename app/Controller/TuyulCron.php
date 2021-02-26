<?php

namespace App\Controller;

use KubAT\PhpSimple\HtmlDomParser;
use WP_Query;

class TuyulCron {
	private $DB;

	public function __construct() {
		global $wpdb;
		$this->DB = $wpdb;
		add_action( 'send_post_to_blogger', [ $this, 'execute' ], 1, 10 );
	}

	/**
	 * Hook to handle cronjob created by user
	 *
	 * @param $job_id int The job id is used to differentiate from other jobs so as not to interfere with other jobs when executed
	 */
	public function execute( $job_id ) {

		$jobs     = $this->DB->get_results( "select * from " . TUYUL_TABLE_JOBS . " where id =" . $job_id . " limit 1" );
		$job      = $jobs[0];
		$meta     = json_decode( $job->meta, true );
		$history  = $this->DB->get_results( $this->DB->prepare( 'select post_id from ' . TUYUL_TABLE_HISTORIES . ' where job_id =%d', $job_id ) );
		$post_ids = [];
		foreach ( $history as $item ) {
			$post_ids[] = (int) $item->post_id;
		}
		$post_ids = array_unique( $post_ids );
		$args     = [
			'cat'          => $meta['category_id'],
			'post_status'  => array( 'publish' ),
			'orderby'      => 'post_date',
			'order'        => 'ASC',
			'post__not_in' => $post_ids
		];
		switch ( $meta['priority'] ) {
			case 'random':
				$args['orderby'] = 'rand';
				break;
			case 'asc':
				$args['orderby'] = 'post_date';
				$args['order']   = 'ASC';
				break;
			case 'desc':
				$args['orderby'] = 'post_date';
				$args['order']   = 'DESC';
				break;
		}
		$query = new WP_Query( $args );
		if ( count( $query->posts ) > 0 ) {
			$post = $query->posts[0];
			$dom  = <<<DOM
$post->post_content
DOM;
			$html = HtmlDomParser::str_get_html( $dom );
			$p    = $html->find( 'p' );
			$p[0]->innertext .= "<!--more-->";
			$html->save();

			$subject = $post->post_title;

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );


			//check if content_tipe is excerpt then we send excerpt content otherwise send full content
			$html = $meta['content_type'] === 'full' ? $html : get_the_excerpt( $post->ID );
			switch ( $job->provider ) {
				case 'email':
					wp_mail( $meta['email'], $subject, $html, $headers );
					break;
				case 'telegram-channel':
					$message = "<a href='" . get_permalink( $post->ID ) . "'>$subject</a>\n\n";
					$html    = str_replace( '<p>', '', $html );
					$html    = str_replace( '</p>', "\n", $html );
					$message .= strip_tags( $html, '<a></a><b><strong><i><em><u><ins><s><strike><del><code><pre><pre>' ) . "\n\n";
					$message .= "Source: " . get_permalink( $post->ID );
					wp_remote_post( "https://api.telegram.org/bot" . $meta['telegram_bot_token'] . "/sendMessage", [
						'body' => [
							'chat_id'    => $meta['telegram_channel_username'],
							'text'       => $message,
							'parse_mode' => 'HTML'
						]
					] );
					break;
			}
			$this->DB->insert( TUYUL_TABLE_HISTORIES, array(
				'post_id' => $post->ID,
				'job_id'  => $job_id,
			) );
		}
	}
}