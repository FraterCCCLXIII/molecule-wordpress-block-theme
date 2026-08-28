<?php
/**
 * Plugin Name: Mailpit SMTP (Docker local)
 * Description: When MAILPIT_SMTP_HOST is set, routes all wp_mail traffic to Mailpit.
 * Version: 1.0.0
 *
 * @package Mailpit_SMTP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Only activate when Docker Compose sets these (see docker-compose.yml).
 */
$mailpit_host = getenv( 'MAILPIT_SMTP_HOST' );
if ( ! is_string( $mailpit_host ) || $mailpit_host === '' ) {
	return;
}

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) {
		$host = getenv( 'MAILPIT_SMTP_HOST' );
		$port = (int) ( getenv( 'MAILPIT_SMTP_PORT' ) ?: 1025 );
		if ( ! is_string( $host ) || $host === '' ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host        = $host;
		$phpmailer->Port        = $port;
		$phpmailer->SMTPAuth    = false;
		$phpmailer->SMTPAutoTLS = false;
		$phpmailer->SMTPSecure  = '';
	},
	999
);

/**
 * WP Mail SMTP: if the saved mailer is an API provider (SendGrid, Sendinblue, …),
 * PHPMailer SMTP settings are ignored and mail never reaches Mailpit. Override
 * options so the plugin uses the SMTP transport to Mailpit.
 *
 * Also disable "optimized" enqueue so messages are sent immediately.
 */
add_action(
	'plugins_loaded',
	static function () {
		add_filter(
			'wp_mail_smtp_options_get',
			static function ( $value, $group, $key ) {
				$host = getenv( 'MAILPIT_SMTP_HOST' );
				if ( ! is_string( $host ) || $host === '' ) {
					return $value;
				}
				$port = (int) ( getenv( 'MAILPIT_SMTP_PORT' ) ?: 1025 );

				if ( 'mail' === $group && 'mailer' === $key ) {
					return 'smtp';
				}
				if ( 'smtp' === $group ) {
					switch ( $key ) {
						case 'host':
							return $host;
						case 'port':
							return $port;
						case 'encryption':
							return 'none';
						case 'auth':
							return false;
						case 'autotls':
							return false;
						default:
							return $value;
					}
				}
				return $value;
			},
			20,
			3
		);

		add_filter( 'wp_mail_smtp_mail_catcher_send_enqueue_email', '__return_false', PHP_INT_MAX );
	},
	999
);
