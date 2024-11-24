#### [unreleased]

#### 1.2.0 / 2024-11-19
* return `WP_Error` in `Language_Pack_API::get_language_pack()` with validation error
* exit gracefully if `Language_Pack_API::get_language_pack()` returns `WP_Error`
* updated error logging for GitHub API rate limits

####  1.1.0 / 2024-11-16
* add API error caching/logging
* always return `$this->repo` in `Language_Pack_API::get_language_pack()`

#### 1.0.1 / 2024-11-12
* fixed a hard-coded 'master' branch in `Language_Pack_API::process_language_pack_package()`

#### 1.0.0 / 2024-11-12
* added WPCS-style linting
* return empty array in `API::get_repo_data()` as appropriate
* lowercase slugs for GlotPress compatibility
* more checks to correctly update appropriate transient
* update to select repository branch
* make work with self-hosted installs of git hosts
* update `Init::can_update()` for parity with GitHub Updater
* update for possible universal EDD SL Updater plugin
* switch to `site_transient_update_{plugins|themes}` filter
* convert to composer dependency from [EDD Translations Updater](https://github.com/afragen/edd-translations-updater) and make more generic for any WordPress plugin or theme
* support EDD Software Licensing `post_edd_sl_{plugin|theme}_updater_setup` action hooks
* update for Bitbucket API 2.0
* initial commit
