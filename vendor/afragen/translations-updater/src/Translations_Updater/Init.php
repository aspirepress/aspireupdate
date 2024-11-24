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

/**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Init
 *
 * @package Fragen\Translations_Updater
 */
class Init {
	use Base;

	/**
	 * Holds calling class for EDD SL Updater.
	 *
	 * @var string
	 */
	private $caller;

	/**
	 * Constructor.
	 *
	 * @param string $caller Namespace of calling class.
	 */
	public function __construct( $caller = null ) {
		$this->caller = $caller;
	}

	/**
	 * Test for proper user capabilities.
	 *
	 * @return bool
	 */
	private function can_update() {
		// WP-CLI access has full capabilities.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			return true;
		}

		$can_user_update = current_user_can( 'update_plugins' ) && current_user_can( 'update_themes' );

		return $can_user_update;
	}

	/**
	 * Start the processing.
	 *
	 * @param mixed $config   [ 'git' => '{github|bitbucket|gitlab|gitea}',
	 *                          'type' => '{plugin|theme}',
	 *                          'slug' => 'my-repo-slug',
	 *                          'version => '1.0',
	 *                          'languages' => 'https://github.com/<owner>/my-translations',
	 *                        ].
	 * @return void|bool
	 */
	public function run( $config ) {
		if ( ! isset( $config['git'], $config['type'], $config['slug'], $config['version'], $config['languages'] ) ) {
			return false;
		}
		$config['branch'] = isset( $config['branch'] ) ? $config['branch'] : 'master';
		if ( $this->can_update() ) {
			$config = $this->sanitize( $config );
			$this->get_remote_repo_data( $config );
		}
	}

	/**
	 * Load relevant action hooks for EDD Software Licensing.
	 */
	public function edd_run() {
		add_action( 'post_edd_sl_plugin_updater_setup', [ $this, 'parse_edd_config' ], 15, 1 );
		add_action( 'post_edd_sl_theme_updater_setup', [ $this, 'parse_edd_config' ], 15, 1 );
	}

	/**
	 * Parse passed config from EDD SL.
	 *
	 * @param array $config EDD SL config array.
	 *
	 * @return void
	 */
	public function parse_edd_config( $config ) {
		$edd_sl_updater = 'EDD\Software_Licensing\Updater';
		if ( $edd_sl_updater !== $this->caller ) {
			if ( 'post_edd_sl_plugin_updater_setup' === current_filter() ) {
				$slug           = array_keys( $config )[0];
				$config         = array_values( $config )[0];
				$config['type'] = 'plugin';
				$config['slug'] = $slug;
			}
			if ( 'post_edd_sl_theme_updater_setup' === current_filter() ) {
				$config['type'] = 'theme';
				$config['slug'] = $config['theme_slug'];
			}
		}
		$this->run( $config );
	}
}
