<?php
namespace AgentMark;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rate_Limiter {
	private $max_requests = 60; // Per hour

	public function check_rate_limit() {
		$ip = $this->get_client_ip();
		$transient_key = 'agentmark_rate_' . md5( $ip );
		$requests = get_transient( $transient_key );

		if ( false === $requests ) {
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
			return true;
		}

		if ( $requests >= $this->max_requests ) {
			return false;
		}

		set_transient( $transient_key, $requests + 1, HOUR_IN_SECONDS );
		return true;
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
