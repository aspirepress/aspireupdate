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
 * Class Base
 *
 * Update WordPress language packs from a git hosted repo.
 *
 * @package Fragen\Translations_Updater
 * @author  Andy Fragen
 */
trait Base {
	/**
	 * Store details of all repositories that are installed.
	 *
	 * @var \stdClass $config
	 */
	protected $config;

	/**
	 * Get remote repo meta data for language-pack.json file.
	 * Initiates remote APIs for data.
	 *
	 * @param array $config plugin/theme config data.
	 *
	 * @return bool
	 */
	final public function get_remote_repo_data( $config ) {
		$config = (object) $config;
		if ( ! isset( $config->languages ) ) {
			return false;
		}

		$this->config[ $config->slug ] = $config;
		$language_pack                 = new Language_Pack( $config, new Language_Pack_API( $config ) );
		$language_pack->run();

		return true;
	}

	/**
	 * Parse URI param returning array of parts.
	 *
	 * @param string $repo_header Repository URI.
	 *
	 * @return array $header
	 */
	final protected function parse_header_uri( $repo_header ) {
		$header_parts         = parse_url( $repo_header );
		$header_path          = pathinfo( $header_parts['path'] );
		$header['original']   = $repo_header;
		$header['scheme']     = isset( $header_parts['scheme'] ) ? $header_parts['scheme'] : null;
		$header['host']       = isset( $header_parts['host'] ) ? $header_parts['host'] : null;
		$header['type']       = explode( '.', $header['host'] )[0] . '_' . $this->repo->type;
		$header['owner']      = trim( $header_path['dirname'], '/' );
		$header['repo']       = $header_path['filename'];
		$header['owner_repo'] = implode( '/', [ $header['owner'], $header['repo'] ] );
		$header['base_uri']   = str_replace( $header_parts['path'], '', $repo_header );
		$header['uri']        = isset( $header['scheme'] ) ? trim( $repo_header, '/' ) : null;

		$header = $this->sanitize( $header );

		return $header;
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys.
	 *
	 * @return array
	 */
	final public function sanitize( $input ) {
		$new_input = [];
		foreach ( array_keys( (array) $input ) as $id ) {
			$new_input[ sanitize_file_name( $id ) ] = sanitize_text_field( $input[ $id ] );
		}

		return $new_input;
	}

	/**
	 * Delete options from database.
	 *
	 * @return void
	 */
	final public function delete_cached_data() {
		global $wpdb;

		$table  = is_multisite() ? $wpdb->base_prefix . 'sitemeta' : $wpdb->base_prefix . 'options';
		$column = is_multisite() ? 'meta_key' : 'option_name';
		// phpcs:disable WordPress.DB
		$delete_string = 'DELETE FROM ' . $table . ' WHERE ' . $column . ' LIKE %s LIMIT 1000';

		$wpdb->query( $wpdb->prepare( $delete_string, [ '%tu-%' ] ) );
		// phpcs:enable
	}
}
