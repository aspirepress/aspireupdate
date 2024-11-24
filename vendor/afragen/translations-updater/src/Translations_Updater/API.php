<?php
/**
 * Translations Updater
 *
 * @package Fragen\Translations_Updater
 * @author  Andy Fragen
 * @license MIT
 * @link    https://github.com/afragen/translations-updater
 */

namespace Fragen\Translations_Updater;

/*
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class API
 *
 * @package Fragen\Translations_Updater
 */
trait API {
	/**
	 * Variable for setting update transient hours.
	 *
	 * @var integer
	 */
	protected static $hours = 12;

	/**
	 * Variable to hold all repository remote info.
	 *
	 * @var array
	 */
	protected $response = [];

	/**
	 * Holds HTTP error code from API call.
	 *
	 * @var array ( $this->repo->slug => $code )
	 */
	protected static $error_code = [];

	/**
	 * Return repo data for API calls.
	 *
	 * @return array
	 */
	final protected function return_repo_type() {
		$arr           = [];
		$arr['type']   = $this->repo->type;
		$arr['branch'] = $this->repo->branch;

		switch ( $this->repo->git ) {
			case 'github':
				$arr['git']           = 'github';
				$arr['base_uri']      = 'https://api.github.com';
				$arr['base_download'] = 'https://github.com';
				break;
			case 'bitbucket':
				$arr['git']           = 'bitbucket';
				$arr['base_uri']      = 'https://bitbucket.org/api';
				$arr['base_download'] = 'https://bitbucket.org';
				break;
			case 'gitlab':
				$arr['git']           = 'gitlab';
				$arr['base_uri']      = 'https://gitlab.com/api/v4';
				$arr['base_download'] = 'https://gitlab.com';
				break;
			case 'gitea':
				$arr['git'] = 'gitea';
				// TODO: make sure this works.
				$arr['base_uri']      = $this->repo->languages . '/api/v1';
				$arr['base_download'] = $this->repo->languages;
				break;
		}

		return $arr;
	}

	/**
	 * Call the API and return a json decoded body.
	 *
	 * @param string $url Repository URL.
	 *
	 * @return boolean|\stdClass
	 */
	final protected function api( $url ) {
		$url  = $this->get_api_url( $url );
		$args = [];

		// Use cached API failure data to avoid hammering the API.
		$response = $this->get_repo_cache( md5( $url ) );
		$cached   = isset( $response['error_cache'] );
		$response = $response ? $response['error_cache'] : $response;
		$response = empty( $response )
			? wp_remote_get( $url, $args )
			: $response;

		$code          = (int) wp_remote_retrieve_response_code( $response );
		$allowed_codes = [ 200 ];

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			error_log( var_export( $response, true ) );

			return $response;
		}

		// Cache HTTP API error code for 60 minutes.
		if ( ! in_array( $code, $allowed_codes, true ) && ! $cached ) {
			$timeout = 60;

			// Set timeout to GitHub rate limit reset.
			$timeout             = static::ratelimit_reset( $response, $this->repo->slug );
			$response['timeout'] = $timeout;
			$this->set_repo_cache( 'error_cache', $response, md5( $url ), "+{$timeout} minutes" );
		}

		static::$error_code[ $this->repo->slug ] = static::$error_code[ $this->repo->slug ] ?? [];
		static::$error_code[ $this->repo->slug ] = array_merge(
			static::$error_code[ $this->repo->slug ],
			[
				'repo' => $this->repo->slug,
				'code' => $code,
				'name' => $this->repo->name ?? $this->repo->slug,
				'git'  => $this->repo->git,
			]
		);
		if ( isset( $response['timeout'] ) ) {
			static::$error_code[ $this->repo->slug ]['wait'] = static::ratelimit_reset( $response, $this->repo->slug );
		}

		if ( isset( $response['timeout'] ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );
			if ( null !== $response_body && property_exists( $response_body, 'message' ) ) {
				$log_message = "Translations Updater Error: {$this->repo->slug} - {$response_body->message}"
				. "\nTime remaining for rate limiting:  {$this->ratelimit_reset($response, $this->repo->slug)} minutes";
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $log_message );
			}
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Return API url.
	 *
	 * @access protected
	 *
	 * @param string $endpoint API endpoint.
	 *
	 * @return string $endpoint
	 */
	final protected function get_api_url( $endpoint ) {
		$type = $this->return_repo_type();

		switch ( $type['git'] ) {
			case 'bitbucket':
			case 'gitea':
				break;
			case 'github':
			case 'gitlab':
				$endpoint = add_query_arg( 'ref', $type['branch'], $endpoint );
				break;
			default:
		}

		return $type['base_uri'] . $endpoint;
	}

	/**
	 * Validate wp_remote_get response.
	 *
	 * @param \stdClass $response API response.
	 *
	 * @return bool true if invalid
	 */
	final protected function validate_response( $response ) {
		return empty( $response ) || isset( $response->message );
	}

	/**
	 * Returns repo cached data.
	 *
	 * @param string|bool $repo Repo name or false.
	 *
	 * @return array|bool false for expired cache
	 */
	final protected function get_repo_cache( $repo = false ) {
		if ( ! $repo ) {
			$repo = isset( $this->repo->slug ) ? $this->repo->slug : 'tu';
		}
		$cache_key = 'tu-' . md5( $repo );
		$cache     = get_site_option( $cache_key );

		if ( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
			return [];
		}

		return $cache;
	}

	/**
	 * Sets repo data for cache in site option.
	 *
	 * @param string      $id       Data Identifier.
	 * @param mixed       $response Data to be stored.
	 * @param string|bool $repo     Repo name or false.
	 *
	 * @return bool
	 */
	final protected function set_repo_cache( $id, $response, $repo = false, $timeout = false ) {
		if ( ! $repo ) {
			$repo = isset( $this->repo->slug ) ? $this->repo->slug : 'tu';
		}
		$cache_key = 'tu-' . md5( $repo );
		$timeout   = $timeout ? $timeout : '+' . static::$hours . ' hours';

		$this->response['timeout'] = strtotime( $timeout );
		$this->response[ $id ]     = $response;

		update_site_option( $cache_key, $this->response );

		return true;
	}

	/**
	 * Calculate and store time until rate limit reset.
	 *
	 * @param array  $response HTTP headers.
	 * @param string $repo     Repo name.
	 *
	 * @return void|int
	 */
	final public static function ratelimit_reset( $response, $repo ) {
		$headers = wp_remote_retrieve_headers( $response );
		$data    = $headers->getAll();
		$wait    = 0;
		if ( isset( $data['x-ratelimit-reset'] ) ) {
			$reset = (int) $data['x-ratelimit-reset'];
			//phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$wait                        = date( 'i', $reset - time() );
			static::$error_code[ $repo ] = static::$error_code[ $repo ] ?? [];
			static::$error_code[ $repo ] = array_merge( static::$error_code[ $repo ], [ 'wait' => $wait ] );

			return $wait;
		}
	}
}
