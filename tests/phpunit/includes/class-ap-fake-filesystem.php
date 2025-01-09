<?php

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

class AP_FakeFilesystem extends WP_Filesystem_Direct {
	/**
	 * Whether paths should exist.
	 *
	 * @var bool|null
	 */
	private $exists;

	/**
	 * Whether paths should be readable.
	 *
	 * @var bool|null
	 */
	private $is_readable;

	/**
	 * Whether paths should be writable.
	 *
	 * @var bool|null
	 */
	private $is_writable;

	/**
	 * Contructor.
	 *
	 * @param bool|null $exists      Whether paths should exist.
	 *                               Default null to use parent implementation.
	 * @param bool|null $is_readable Whether paths should be readable.
	 *                               Default null to use parent implementation.
	 * @param bool|null $is_writable Whether paths should be writable.
	 *                               Default null to use parent implementation.
	 */
	public function __construct( $exists = null, $is_readable = null, $is_writable = null ) {
		$this->exists      = $exists;
		$this->is_readable = $is_readable;
		$this->is_writable = $is_writable;
	}

	/**
	 * Checks whether a path exists.
	 *
	 * @param string $path The path to check.
	 * @return bool Whether the path exists.
	 */
	public function exists( $path ) {
		if ( null === $this->exists ) {
			return parent::exists( $path );
		}
		return $this->exists;
	}

	/**
	 * Checks whether a path is readable.
	 *
	 * @param string $path The path to check.
	 * @return bool Whether the path is readable.
	 */
	public function is_readable( $path ) {
		if ( null === $this->is_readable ) {
			return parent::is_readable( $path );
		}
		return $this->is_readable;
	}

	/**
	 * Checks whether a path is writable.
	 *
	 * @param string $path The path to check.
	 * @return bool Whether the path is writable.
	 */
	public function is_writable( $path ) {
		if ( null === $this->is_writable ) {
			return parent::is_writable( $path );
		}
		return $this->is_writable;
	}
}
