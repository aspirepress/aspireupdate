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
	 * Reads entire file into an array with options for limiting the number of lines and direction from the the lines are counted.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Path to the file.
	 * @param int    $number_of_lines The number of lines to read. Default is -1 (read all lines).
	 * @param bool   $count_bottom_to_top Count the lines from the bottom up. Default is false (count from top to bottom).
	 *
	 * @return array|false File contents in an array on success, false on failure.
	 */
	public function get_contents_array( $file, $number_of_lines = -1, $count_bottom_to_top = false ) {
		if ( ! $this->exists( $file ) ) {
			return false;
		}

		if ( -1 === $number_of_lines ) {
			return @file( $file );
		}

		$handle = @fopen( $file, 'r' );
		if ( ! $handle ) {
			return false;
		}

		$lines      = [];
		$line_count = 0;

		// phpcs:disable Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		/**
		 * This is a valid and intentional use.
		 */
		while ( ( $line = fgets( $handle ) ) !== false ) {
			$lines[] = rtrim( $line, "\r\n" );
			++$line_count;

			if ( $count_bottom_to_top ) {
				if ( $number_of_lines > 0 && $line_count > $number_of_lines ) {
					array_shift( $lines );
				}
			} elseif ( $number_of_lines > 0 && $line_count >= $number_of_lines ) {
					break;
			}
		}
		// phpcs:enable

		fclose( $handle );

		return $lines;
	}

	/**
	 * Write contents to a file with additional modes.
	 *
	 * @param string    $file The path to the file.
	 * @param string    $contents The content to write.
	 * @param int|false $mode     Optional. The file permissions as octal number, usually 0644.
	 *                            Default false.
	 * @param string    $write_mode The write mode:
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

		$handle = @fopen( $file, $write_mode );
		if ( ! $handle ) {
			return false;
		}

		mbstring_binary_safe_encoding();
		$data_length   = strlen( $contents );
		$bytes_written = fwrite( $handle, $contents );
		reset_mbstring_encoding();

		fclose( $handle );

		if ( $data_length !== $bytes_written ) {
			return false;
		}

		$this->chmod( $file, $mode );

		return true;
	}
}
