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
 * Class Language_Pack
 *
 * @package Fragen\Translations_Updater
 */
class Language_Pack {
	use Base;

	/**
	 * Variable containing the plugin/theme object.
	 *
	 * @var \stdClass $repo
	 */
	protected $repo;

	/**
	 * Variable containing repo API object.
	 *
	 * @var \stdClass
	 */
	protected $repo_api;

	/**
	 * Language_Pack constructor.
	 *
	 * @param \stdClass                                      $config Config data.
	 * @param \Fragen\Translations_Updater\Language_Pack_API $api    Language_Pack_API object.
	 */
	public function __construct( $config, Language_Pack_API $api ) {
		if ( null === $config->languages ) {
			return;
		}

		$this->repo     = $config;
		$this->repo_api = $api;
	}

	/**
	 * Do the Language Pack integration.
	 */
	public function run() {
		$headers = $this->parse_header_uri( $this->repo->languages );
		$repo    = $this->repo_api->get_language_pack( $headers );
		if ( is_wp_error( $repo ) ) {
			return;
		}
		$this->config[ $repo->slug ] = $repo;

		add_filter( 'site_transient_update_plugins', [ $this, 'update_site_transient' ] );
		add_filter( 'site_transient_update_themes', [ $this, 'update_site_transient' ] );
	}

	/**
	 * Add language translations to update_plugins or update_themes transients.
	 *
	 * @param \stdClass $transient Update transient.
	 *
	 * @return mixed
	 */
	public function update_site_transient( $transient ) {
		$locales = get_available_languages();
		$locales = ! empty( $locales ) ? $locales : [ get_locale() ];
		$repos   = [];

		if ( ! isset( $transient->translations ) ) {
			return $transient;
		}

		if ( 'site_transient_update_plugins' === current_filter() ) {
			$translations = wp_get_installed_translations( 'plugins' );
		}
		if ( 'site_transient_update_themes' === current_filter() ) {
			$translations = wp_get_installed_translations( 'themes' );
		}

		$repos = array_filter(
			(array) $this->config,
			function ( $e ) {
				return isset( $e->language_packs );
			}
		);

		foreach ( $repos as $repo ) {
			if ( ! str_contains( current_filter(), $repo->type ) ) {
				continue;
			}
			foreach ( $locales as $locale ) {
				$lang_pack_mod   = isset( $repo->language_packs->$locale )
					? strtotime( $repo->language_packs->$locale->updated )
					: 0;
				$translation_mod = isset( $translations[ $repo->slug ][ $locale ] )
					? strtotime( $translations[ $repo->slug ][ $locale ]['PO-Revision-Date'] )
					: 0;
				if ( $lang_pack_mod > $translation_mod ) {
					$transient->translations[] = (array) $repo->language_packs->$locale;
				}
			}
		}

		$transient->translations = array_unique( $transient->translations, SORT_REGULAR );

		return $transient;
	}
}
