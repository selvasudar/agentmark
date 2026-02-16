<?php
namespace AgentMark;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Analytics {
	public function log_access( $post_id, $endpoint_type = 'md' ) {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) 
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) 
			: 'Unknown';
		
		$log_entry = [
			'post_id' => $post_id,
			'endpoint' => $endpoint_type,
			'user_agent' => $user_agent,
			'timestamp' => current_time( 'mysql' ),
			'ip' => $this->get_client_ip(),
		];
		
		// Store in option (rotate after 1000 entries)
		$logs = get_option( 'agentmark_access_logs', [] );
		$logs[] = $log_entry;
		
		if ( count( $logs ) > 1000 ) {
			$logs = array_slice( $logs, -500 ); // Keep last 500
		}
		
		update_option( 'agentmark_access_logs', $logs, false );
	}
	
	public function get_stats() {
		$logs = get_option( 'agentmark_access_logs', [] );
		
		$stats = [
			'total_requests' => count( $logs ),
			'md_requests' => 0,
			'llms_requests' => 0,
			'unique_posts' => [],
			'top_agents' => [],
		];
		
		foreach ( $logs as $log ) {
			if ( $log['endpoint'] === 'md' ) {
				$stats['md_requests']++;
				$stats['unique_posts'][] = $log['post_id'];
			} else {
				$stats['llms_requests']++;
			}
			
			if ( ! isset( $stats['top_agents'][ $log['user_agent'] ] ) ) {
				$stats['top_agents'][ $log['user_agent'] ] = 0;
			}
			$stats['top_agents'][ $log['user_agent'] ]++;
		}
		
		$stats['unique_posts'] = count( array_unique( $stats['unique_posts'] ) );
		arsort( $stats['top_agents'] );
		
		return $stats;
	}
	
	private function get_client_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return $ip;
	}
}
