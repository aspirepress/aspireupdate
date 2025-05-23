<?php
/**
 * The Class for Rewriting the Default API.
 *
 * @package aspire-update
 */

namespace AspireUpdate;

/**
 * The Class for Rewriting the Default API.
 */
class API_Rewrite {
	/**
	 * The default API Host.
	 *
	 * @var string
	 */
	private $default_host = 'api.wordpress.org';

	/**
	 * The redirected API Host.
	 *
	 * @var string
	 */
	private $redirected_host;

	/**
	 * Disable SSL.
	 *
	 * @var boolean
	 */
	private $disable_ssl;

	/**
	 * API Key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * The Constructor.
	 *
	 * @param string  $redirected_host The host to redirect to.
	 * @param boolean $disable_ssl     Disable SSL.
	 * @param string  $api_key         The API key to use with the host.
	 */
	public function __construct( $redirected_host, $disable_ssl, $api_key ) {
		if ( 'debug' === $redirected_host ) {
			$this->redirected_host = $this->default_host;
		} else {
			$this->redirected_host = strtolower( $redirected_host );
		}
		$this->disable_ssl = $disable_ssl;
		$this->api_key     = $api_key;

		if ( Admin_Settings::get_instance()->get_setting( 'enable', false ) ) {
			add_filter( 'pre_http_request', [ $this, 'pre_http_request' ], PHP_INT_MAX, 3 );
		}
	}

	/**
	 * Add Authorization Header if API Key found.
	 *
	 * @param array $args The HTTP request arguments.
	 *
	 * @return array $args The modified HTTP request arguments.
	 */
	private function add_authorization_header( $args ) {
		if ( '' !== $this->api_key ) {
			Debug::log_string( __( 'API Key Authorization header added.', 'aspireupdate' ) );
			if ( ! isset( $args['headers'] ) ) {
				$args['headers'] = [];
			}
			$args['headers']['Authorization'] = 'Bearer ' . $this->api_key;
		}
		return $args;
	}

	/**
	 * Add Accept JSON header if the request is not for a file asset.
	 *
	 * @param array $args The HTTP request arguments.
	 *
	 * @return array $args The modified HTTP request arguments.
	 */
	private function add_accept_json_header( $args, $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		// Check if the URL points to a .php file or has no extension.
		if ( $path && preg_match( '#/[^/]+(\.php|/)$#', $path ) ) {
			Debug::log_string( __( 'Accept JSON Header added for API calls.', 'aspireupdate' ) );
			if ( ! isset( $args['headers'] ) ) {
				$args['headers'] = [];
			}
			$args['headers']['Accept'] = 'application/json';
		}
		return $args;
	}

	/**
	 * Adding cache buster parameter for AC beta test.  Will remove this after Beta.
	 *
	 * @param string $url The URL.
	 *
	 * @return string $url The updated URL.
	 */
	private function add_cache_buster( $url ) {
		Debug::log_string( __( 'Cache Buster Added to URL', 'aspireupdate' ) );
		return add_query_arg( 'cache_buster', time(), $url );
	}

	/**
	 * Get non-API assets.
	 *
	 * @param string $asset_type The type of asset.
	 *
	 * @return array An associative array of non-API asset paths and data.
	 */
	private function get_non_api_assets( $asset_type ) {
		switch ( $asset_type ) {
			case 'plugins':
				return array_filter(
					get_plugins(),
					static function ( $asset ) {
						return ! empty( $asset['UpdateURI'] );
					}
				);
			case 'themes':
				return array_filter(
					wp_get_themes(),
					static function ( $asset ) {
						return ! empty( $asset->get( 'UpdateURI' ) );
					}
				);
			default:
				return [];
		}
	}

	/**
	 * Get the request type.
	 *
	 * @param string $url The URL.
	 *
	 * @return string The request type.
	 */
	private function get_request_type( $url ) {
		if ( false !== stripos( $url, '/update-check/' ) ) {
			return 'update';
		}

		if ( false !== stripos( $url, '/info/' ) ) {
			return 'info';
		}

		return '';
	}

	/**
	 * Get the asset type.
	 *
	 * @param string $url The URL.
	 *
	 * @return string The asset type.
	 */
	private function get_asset_type( $url ) {
		if ( false !== stripos( $url, '/plugins/' ) ) {
			return 'plugins';
		} elseif ( false !== stripos( $url, '/themes/' ) ) {
			return 'themes';
		}

		return '';
	}

	/**
	 * Rewrite the API End points.
	 *
	 * @param mixed  $response The response for the request.
	 * @param array  $parsed_args The arguments for the request.
	 * @param string $url The URL for the request.
	 *
	 * @return mixed The response or false.
	 */
	public function pre_http_request( $response, $parsed_args, $url ) {
		if (
			isset( $this->default_host ) &&
			( '' !== $this->default_host ) &&
			isset( $this->redirected_host ) &&
			( '' !== $this->redirected_host )
		) {
			if ( false !== strpos( $url, $this->default_host ) ) {
				Debug::log_string( __( 'Default API Found: ', 'aspireupdate' ) . $url );

				if ( false !== $response ) {
					$admin_settings = \AspireUpdate\Admin_Settings::get_instance();
					$compatibility  = $admin_settings->get_setting( 'compatibility' );

					if ( ! empty( $compatibility['skip_rewriting_on_existing_response'] ) ) {
						Debug::log_string(
							sprintf(
								/* translators: 1: The options' name, 2: The constant's name, 3: The explicitly required value. */
								__( 'API rewriting has been skipped because the response has already been changed. Enable the %1$s option or set "%1$s" in the %2$s constant to %3$s to continue with API rewriting in future.', 'aspireupdate' ),
								'skip_rewriting_on_existing_response',
								'AP_COMPATIBILITY',
								'true'
							)
						);
						return $response;
					}
				}

				if ( false === filter_var( $this->redirected_host, FILTER_VALIDATE_URL ) ) {
					$error_message = __( 'Your API host is not a valid URL.', 'aspireupdate' );
					Debug::log_string(
						sprintf(
							/* translators: %s: The error message. */
							__( 'Request Failed: %s', 'aspireupdate' ),
							$error_message
						)
					);
					return new \WP_Error( 'invalid_host', $error_message );
				}

				if ( $this->default_host !== $this->redirected_host ) {
					if ( $this->disable_ssl ) {
						Debug::log_string( __( 'SSL Verification Disabled', 'aspireupdate' ) );
						$parsed_args['sslverify'] = false;
					}

					$parsed_args = $this->add_authorization_header( $parsed_args );
					$parsed_args = $this->add_accept_json_header( $parsed_args, $url );
					if ( defined( 'AP_DEBUG_BYPASS_CACHE' ) && AP_DEBUG_BYPASS_CACHE ) {
						$url = $this->add_cache_buster( $url );
					}
					$protocol    = wp_parse_url( $url, PHP_URL_SCHEME );
					$updated_url = str_replace(
						"{$protocol}://{$this->default_host}",
						untrailingslashit( $this->redirected_host ),
						$url
					);
					Debug::log_string( __( 'API Rerouted to: ', 'aspireupdate' ) . $updated_url );

					Debug::log_request( $parsed_args );

					$request_type       = $this->get_request_type( $updated_url );
					$asset_type         = $this->get_asset_type( $updated_url );
					$is_plugin_or_theme = $asset_type && in_array( $asset_type, [ 'plugins', 'themes' ], true );
					$non_api_assets     = [];

					// Remove non-API assets from update requests.
					if ( 'update' === $request_type && $is_plugin_or_theme ) {
						// This is also used later to remove non-API assets from update responses.
						$non_api_assets = $this->get_non_api_assets( $asset_type );

						if ( ! empty( $parsed_args['body'][ $asset_type ] ) ) {
							$assets     = json_decode( $parsed_args['body'][ $asset_type ], true );
							$api_assets = array_diff_key( $assets[ $asset_type ], $non_api_assets );

							if ( $assets[ $asset_type ] !== $api_assets ) {
								$assets[ $asset_type ] = $api_assets;

								Debug::log_string(
									sprintf(
										/* translators: %s: The asset type. */
										__( 'Removed non-API %s from the update request.', 'aspireupdate' ),
										'plugins' === $asset_type ? __( 'plugins', 'aspireupdate' ) : __( 'themes', 'aspireupdate' )
									)
								);
							}

							$parsed_args['body'][ $asset_type ] = wp_json_encode( $assets );
						}
					}

					/**
					 * Temporarily Unhook Filter to prevent recursion.
					 */
					remove_filter( 'pre_http_request', [ $this, 'pre_http_request' ], PHP_INT_MAX );
					$response = wp_remote_request( $updated_url, $parsed_args );
					add_filter( 'pre_http_request', [ $this, 'pre_http_request' ], PHP_INT_MAX, 3 );

					Debug::log_response( $response );

					$response_code         = wp_remote_retrieve_response_code( $response );
					$codes_to_pass_through = [
						200,
						404,
					];
					if ( ! in_array( $response_code, $codes_to_pass_through, true ) ) {
						$message = wp_remote_retrieve_response_message( $response );
						Debug::log_string(
							sprintf(
								/* translators: %s: The error message. */
								__( 'Request Failed: %s', 'aspireupdate' ),
								$message
							)
						);
						return new \WP_Error( 'failed_request', $message );
					}

					if ( $is_plugin_or_theme ) {
						// Remove non-API assets from update responses.
						if ( 'update' === $request_type && ! empty( $non_api_assets ) ) {
							$body = json_decode( $response['body'], true );

							if ( ! empty( $body[ $asset_type ] ) ) {
								$asset_paths = array_keys( $body[ $asset_type ] );

								$removed = false;
								foreach ( $asset_paths as $asset_path ) {
									if ( array_key_exists( $asset_path, $non_api_assets ) ) {
										unset( $body[ $asset_type ][ $asset_path ] );
										$removed = true;
									}
								}

								$response['body'] = wp_json_encode( $body );

								if ( $removed ) {
									Debug::log_string(
										sprintf(
											/* translators: %s: The asset type. */
											__( 'Removed non-API %s from the update response.', 'aspireupdate' ),
											'plugins' === $asset_type ? __( 'plugins', 'aspireupdate' ) : __( 'themes', 'aspireupdate' )
										)
									);
								}
							}
						}

						// Remove AspireUpdate from information responses.
						if ( 'info' === $request_type && 'plugins' === $asset_type && false === stripos( $updated_url, 'slug' ) ) {
							$body = json_decode( $response['body'], true );

							if ( ! empty( $body[ $asset_type ] ) ) {
								foreach ( $body[ $asset_type ] as $asset_key => $asset_data ) {
									if ( isset( $asset_data['slug'] ) && 'aspireupdate' === strtolower( $asset_data['slug'] ) ) {
										unset( $body[ $asset_type ][ $asset_key ] );

										Debug::log_string(
											sprintf(
												/* translators: %s: AspireUpdate. */
												__( 'Removed %s from the response.', 'aspireupdate' ),
												'AspireUpdate'
											)
										);

										/*
										 * Do not `break` in case more than one entry with
										 * the slug has been injected into the response.
										 */
									}
								}

								$response['body'] = wp_json_encode( $body );
							}
						}
					}

					return $response;

				}
			}
		}
		return $response;
	}
}
