<?php
/**
 * The Class for WordPress Direct Filesystem with optimized read and write routines.
 *
 * @package aspire-update
 */

namespace AspireUpdate;

/**
 * The Class for WordPress Direct Filesystem with optimized read and write routines.
 */
class Filesystem_Direct extends \WP_Filesystem_Direct {
	/**
	 * Write contents to a file with additional modes.
	 *
	 * @param string $file The path to the file.
	 * @param string $contents The content to write.
	 * @param int|false $mode     Optional. The file permissions as octal number, usually 0644.
	 *                            Default false.
	 * @param string $write_mode The write mode:
	 *                     'w'  - Overwrite the file (default).
	 *                     'a'  - Append to the file.
	 *                     'x'  - Create a new file and write, fail if the file exists.
	 *                     'c'  - Open the file for writing, but do not truncate.
	 * @return bool True on success, false on failure.
	 */
	public function put_contents( $file, $contents, $mode = false, $write_mode = 'w' ) {
		$valid_write_modes = [ 'w', 'a', 'x', 'c' ];
		if ( ! in_array( $write_mode, $valid_write_modes, true ) ) {
			return false;
		}
		$fp = @fopen( $file, $write_mode );

		if ( ! $fp ) {
			return false;
		}

		mbstring_binary_safe_encoding();

		$data_length = strlen( $contents );

		$bytes_written = fwrite( $fp, $contents );

		reset_mbstring_encoding();

		fclose( $fp );

		if ( $data_length !== $bytes_written ) {
			return false;
		}

		$this->chmod( $file, $mode );

		return true;
	}
}
