<?php
/**
 * The Class for managing the plugins Workflow.
 *
 * @package aspire-update
 */

namespace AspireUpdate;

/**
 * The Class for managing the plugins Workflow.
 */
class Controller {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		Admin_Settings::get_instance();
		Plugins_Screens::get_instance();
		Themes_Screens::get_instance();
		Branding::get_instance();
		$this->api_rewrite();
		add_action( 'wp_ajax_aspireupdate_clear_log', [ $this, 'clear_log' ] );
		add_action( 'wp_ajax_aspireupdate_read_log', [ $this, 'read_log' ] );
		add_filter( 'site_transient_update_plugins', [ $this, 'check_self_update_notifications' ] );
	}

	/**
	 * Enable API Rewrites based on the Users settings.
	 *
	 * @codeCoverageIgnore Side-effects are from other methods already covered by tests.
	 *
	 * @return void
	 */
	private function api_rewrite() {
		$admin_settings = Admin_Settings::get_instance();

		if ( $admin_settings->get_setting( 'enable', false ) ) {
			$api_host = $admin_settings->get_setting( 'api_host', '' );
		} else {
			$api_host = 'debug';
		}

		if ( isset( $api_host ) && ( '' !== $api_host ) ) {
			$enable_debug = $admin_settings->get_setting( 'enable_debug', false );
			$disable_ssl  = $admin_settings->get_setting( 'disable_ssl_verification', false );
			$api_key      = $admin_settings->get_setting( 'api_key', '' );
			if ( $enable_debug && $disable_ssl ) {
				new API_Rewrite( $api_host, true, $api_key );
			} else {
				new API_Rewrite( $api_host, false, $api_key );
			}
		}
	}

	/**
	 * Ajax action to clear the Log file.
	 *
	 * @codeCoverageIgnore Cannot be tested. Results in script termination.
	 *
	 * @return void
	 */
	public function clear_log() {
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		if (
			! current_user_can( $capability ) ||
			! isset( $_POST['nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'aspireupdate-ajax' )
		) {
			wp_send_json_error(
				[
					'message' => __( 'Error: You are not authorized to access this resource.', 'aspireupdate' ),
				]
			);
		}

		$status = Debug::clear();
		if ( is_wp_error( $status ) ) {
			wp_send_json_error(
				[
					'message' => $status->get_error_message(),
				]
			);
		}

		wp_send_json_success(
			[
				'message' => __( 'Log file cleared successfully.', 'aspireupdate' ),
			]
		);
	}

	/**
	 * Ajax action to read the Log file.
	 *
	 * @codeCoverageIgnore Cannot be tested. Results in script termination.
	 *
	 * @return void
	 */
	public function read_log() {
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		if (
			! current_user_can( $capability ) ||
			! isset( $_POST['nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'aspireupdate-ajax' )
		) {
			wp_send_json_error(
				[
					'message' => __( 'Error: You are not authorized to access this resource.', 'aspireupdate' ),
				]
			);
		}

		$content = Debug::read( 1000 );
		if ( is_wp_error( $content ) ) {
			wp_send_json_error(
				[
					'message' => $content->get_error_message(),
				]
			);
		}

		wp_send_json_success(
			[
				'content' => $content,
			]
		);
	}

	/**
	 * Hide Aspire Update plugin from plugin update notifications.
	 * This is to prevent supply chain attacks via unverified API providers.
	 *
	 * @param object $value The Update Notifications Data.
	 *
	 * @return object $value The Update Notifications Data.
	 */
	public function check_self_update_notifications( $value ) {
		if ( isset( $value->response['aspireupdate/aspire-update.php'] ) ) {
			unset( $value->response['aspireupdate/aspire-update.php'] );
		}
		return $value;
	}
}
