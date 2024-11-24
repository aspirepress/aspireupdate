
# Translations Updater

* Contributors: [Andy Fragen](https://github.com/afragen)
* Tags: plugins, themes, edd software licensing, language pack, updater
* Requires at least: 4.6
* Requires PHP: 5.4
* Donate link: <http://thefragens.com/translations-updater-donate>
* License: MIT
* License URI: <http://www.opensource.org/licenses/MIT>

## Description

This framework allows for decoupled language pack updates for your WordPress plugins or themes that are hosted on public repositories in GitHub, Bitbucket, GitLab, or Gitea.

 The URI should point to a repository that contains the translations files. Refer to [Git Updater Translations](https://github.com/afragen/git-updater-translations) as an example. It is created using the [Language Pack Maker](https://github.com/afragen/language-pack-maker). The repo **must** be a public repo.

## Usage

Install via Composer: `composer require afragen/translations-updater:^1`

**Prior to release use the following command**
`composer require afragen/translations-updater:dev-<branch>` currently `dev-master`

Add `require_once __DIR__ . '/vendor/autoload.php';` to the main plugin file or theme's functions.php file.

A configuration array with the following format is needed. All array elements are required.

```php
add_action( 'admin_init', function() {
	$config = [
		'git'       => '(github|bitbucket|gitlab|gitea)',
		'type'      => '(plugin|theme)',
		'slug'      => 'my-repo-slug', // Should be lowercase.
		'version'   => 'my-repo-version', // Current version of plugin|theme.
		'languages' => 'https://my-path-to/language-packs',
		'branch'    => 'master', // Default (optional).
	];

	( new \Fragen\Translations_Updater\Init() )->run( $config );
} );
```

If you wish to delete the data stored in the options table associated with this framework you will need to issue the following command.

```php
( new \Fragen\Translations_Updater\Init() )->delete_cached_data();
```

## EDD Software Licensing Usage

If using this framework with EDD Software Licensing you will need to update to the latest versions of the updaters in the EDD Software Licensing sample code to ensure the appropriate action hooks are present.

You will need to add two key/value pairs to your setup array similar to the following,
```php
'git'       => 'github',
'languages' => 'https://github.com/<USER>/my-language-pack',
```

You will need to include the following command to your bootstrap file to activate the updater.

```php
( new \Fragen\Translations_Updater\Init( __NAMESPACE__ ) )->edd_run();
```

### Plugins

You must add two additional key/value pairs to the setup array in your `EDD_SL_Plugin_Updater` setup. The array will be similar to the following from the `edd-sample-plugin.php` file.

```php
	$edd_updater = new EDD_SL_Plugin_Updater( EDD_SAMPLE_STORE_URL, __FILE__, array(
			'version'   => '1.0',                // current version number
			'license'   => $license_key,         // license key (used get_option above to retrieve from DB)
			'item_name' => EDD_SAMPLE_ITEM_NAME, // name of this plugin
			'author'    => 'Pippin Williamson',  // author of this plugin
			'beta'      => false,
			'git'       => 'bitbucket',
			'languages' => 'https://bitbucket.org/afragen/test-language-pack',
		)
```

### Themes

You must add two additional key/value pairs to the setup array in your `EDD_Theme_Updater_Admin` setup. The array will be similar to the following from the `edd-sample-theme/updater/theme-updater.php` file.

```php
$updater = new EDD_Theme_Updater_Admin(

	// Config settings
	$config = array(
		'remote_api_url' => 'https://easydigitaldownloads.com', // Site where EDD is hosted
		'item_name'      => 'Theme Name', // Name of theme
		'theme_slug'     => 'theme-slug', // Theme slug
		'version'        => '1.0.0', // The current version of this theme
		'author'         => 'Easy Digital Downloads', // The author of this theme
		'download_id'    => '', // Optional, used for generating a license renewal link
		'renew_url'      => '', // Optional, allows for a custom license renewal link
		'beta'           => false, // Optional, set to true to opt into beta versions
		'git'            => 'github',
		'languages'      => 'https://github.com/<USER>/my-language-pack',
	),
	...
```
