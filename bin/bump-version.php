<?php
/**
 * BumpVersion - Utility to update version numbers in project files.
 *
 * Usage:
 *   php bump-version.php <version> [--dry-run]
 *
 * Example:
 *   php bump-version.php 1.2.3
 *   php bump-version.php 1.2.3 --dry-run
 */

class BumpVersion {
	/**
	 * The version string to set.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Whether to perform a dry run (no files are written).
	 *
	 * @var bool
	 */
	private $dry_run;

	/**
	 * Constructor.
	 *
	 * @param string $version Version string (e.g., 1.2.3).
	 * @param bool   $dry_run Optional. If true, only preview changes. Default false.
	 */
	public function __construct( $version, $dry_run = false ) {
		$this->version = $version;
		$this->dry_run = $dry_run;
	}

	/**
	 * Run the version bump process.
	 *
	 * @return void
	 */
	public function run() {
		$this->print_header();
		//$this->update_composer();
		$this->update_package();
		$this->update_files();
		$this->update_potfiles();
		$this->print_footer();
	}

	/**
	 * Print a message, add dry run prefix if applicable.
	 *
	 * @param string $message The message to print.
	 * @return void
	 */
	private function print_message( $message ) {
		echo ( $this->dry_run ? 'DRY RUN : ' : '' );
		echo $message . PHP_EOL;
	}

	/**
	 * Print the header message.
	 *
	 * @return void
	 */
	private function print_header() {
		$this->print_message( "Bumping version to {$this->version}" );
	}

	/**
	 * Print the footer message.
	 *
	 * @return void
	 */
	private function print_footer() {
		$this->print_message( "Version bump completed: {$this->version}" );
	}

	/**
	 * Update the version in composer.json.
	 *
	 * @return void
	 */
	private function update_composer() {
		$composer_path = __DIR__ . '/../composer.json';
		if ( ! file_exists( $composer_path ) ) {
			$this->print_message( 'composer.json not found' );
			return;
		}
		$composer            = json_decode( file_get_contents( $composer_path ), true );
		$composer['version'] = $this->version;

		if ( ! $this->dry_run ) {
			file_put_contents( $composer_path, json_encode( $composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL );
		}
		$this->print_message( 'Updated version in composer.json' );
	}

	/**
	 * Update the version in package.json if it exists.
	 *
	 * @return void
	 */
	private function update_package() {
		$package_path = __DIR__ . '/../package.json';
		if ( ! file_exists( $package_path ) ) {
			$this->print_message( 'package.json not found' );
			return;
		}
		$package            = json_decode( file_get_contents( $package_path ), true );
		$package['version'] = $this->version;

		if ( ! $this->dry_run ) {
			file_put_contents( $package_path, json_encode( $package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL );
		}
		$this->print_message( 'Updated version in package.json' );

		if ( ! $this->dry_run ) {
			$this->print_message( 'Running npm install...' );
			chdir( dirname( $package_path ) );
			$npm_output = [];
			$npm_status = 0;
			exec( 'npm install 2>&1', $npm_output, $npm_status );
			$this->print_message( 'npm install output:' );
			foreach ( $npm_output as $line ) {
				$this->print_message( $line );
			}
			if ( $npm_status === 0 ) {
				$this->print_message( 'npm install completed successfully.' );
			} else {
				$this->print_message( 'npm install failed.' );
				$this->print_message( 'Please run "npm i" manually to update package-lock.json.' );
			}
		}
	}

	/**
	 * Update version numbers in plugin.php, readme.txt, and readme.md.
	 *
	 * @return void
	 */
	private function update_files() {
		$files = [
			[
				'path'     => __DIR__ . '/../aspire-update.php',
				'patterns' => [
					'/(^[ \t\/*#@]*Version:\s*)([\d\.]+)/mi' => function ( $matches ) {
						return $matches[1] . $this->version;
					},
					'/define\(\s*\'AP_VERSION\'\s*,\s*\'([\d\.]+)\'\s*\)\s*;/i' => function ( $matches ) {
						return str_replace( $matches[1], $this->version, $matches[0] ); },
				],
			],
			[
				'path'     => __DIR__ . '/../readme.txt',
				'patterns' => [
					'/(^Stable tag:\s*)([\d\.]+)/mi' => function ( $matches ) {
						return $matches[1] . $this->version; },
				],
			],
			[
				'path'     => __DIR__ . '/../.github/ISSUE_TEMPLATE/BugReport.yml',
				'patterns' => [
					'/\-\s*\d+\.\d+\.\d+\s*\(Latest\)/' => '- ' . $this->version . ' (Latest)',
				],
			],
			[
				'path'     => __DIR__ . '/../assets/playground/blueprint.json',
				'patterns' => [
					'/https:\/\/github-proxy\.com\/proxy\/\?repo=AspirePress\/AspireUpdate&release=\d+\.\d+\.\d+/' => 'https://github-proxy.com/proxy/?repo=AspirePress/AspireUpdate&release=' . $this->version,
				],
			],
		];

		foreach ( $files as $file ) {
			$file_path = $file['path'];
			if ( ! file_exists( $file_path ) ) {
				$this->print_message( "File not found: $file_path" );
				continue;
			}

			$original = file_get_contents( $file_path );
			$modified = $original;

			foreach ( $file['patterns'] as $pattern => $replacement ) {
				if ( is_callable( $replacement ) ) {
					$modified = preg_replace_callback( $pattern, $replacement->bindTo( $this ), $modified );
				} else {
					$modified = preg_replace( $pattern, $replacement, $modified );
				}
			}

			if ( $original === $modified ) {
				$this->print_message( "No version changes needed: $file_path" );
			} else {
				if ( ! $this->dry_run ) {
					file_put_contents( $file_path, $modified );
				}
				$this->print_message( "Updated version in: $file_path" );
			}
		}
	}

	/**
	 * Update the POT files.
	 *
	 * @return void
	 */
	private function update_potfiles() {
		$potfiles = [
			__DIR__ . '/../languages/aspireupdate.pot',
		];

		foreach ( $potfiles as $potfile ) {
			if ( ! file_exists( $potfile ) ) {
				$this->print_message( "POT file not found: $potfile" );
				continue;
			}

			if ( ! $this->dry_run ) {
				$this->print_message( "Updating POT file: $potfile" );
				$this->print_message( 'Running wp i18n make-pot for: ' . $potfile );

				$is_windows   = strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
				$headers_json = json_encode(
					[
						'Report-Msgid-Bugs-To' => 'https://github.com/aspirepress/aspireupdate/issues',
					],
					JSON_UNESCAPED_SLASHES
				);
				if ( $is_windows ) {
					$headers_arg = '"' . str_replace( '"', '\"', $headers_json ) . '"';
					$cmd         = '.\\vendor\\wp-cli\\wp-cli\\bin\\wp i18n make-pot . languages/aspireupdate.pot --headers=' . $headers_arg;
				} else {
					$cmd = './vendor/wp-cli/wp-cli/bin/wp i18n make-pot . ./languages/aspireupdate.pot --headers=' . escapeshellarg( $headers_json );
				}
				$pot_output = [];
				$pot_status = 0;
				exec( $cmd . ' 2>&1', $pot_output, $pot_status );
				foreach ( $pot_output as $line ) {
					$this->print_message( $line );
				}
				if ( $pot_status === 0 ) {
					$this->print_message( 'POT file updated successfully: ' . $potfile );
				} else {
					$this->print_message( 'Failed to update POT file: ' . $potfile );
					$this->print_message( 'Please run "' . $cmd . '" manually to update pot file.' );
				}
			}
		}
	}
}

$usage = <<<USAGE
Usage:
  php bump-version.php <version> [--dry-run]

Example:
  php bump-version.php 1.2.3
  php bump-version.php 1.2.3 --dry-run

USAGE;

if ( $argc < 2 || ! preg_match( '/^\d+\.\d+\.\d+$/', $argv[1] ) ) {
	echo $usage;
	exit( 1 );
}

$version = $argv[1];
$dry_run = in_array( '--dry-run', $argv, true );

$bumper = new BumpVersion( $version, $dry_run );
$bumper->run();
