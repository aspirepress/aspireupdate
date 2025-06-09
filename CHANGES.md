[unreleased]

#### 1.0.1
* Assets: Added plugin icon and banner files.
* Dependencies: Updated Git Updater Lite to version 2.3.2.
* Dependencies: Updated PHP Parser to version 5.5.0.
* Workflows: Changed the Playground workflow to manual-only.

#### 1.0
* Removed the Aspire Cloud Bleeding Edge Endpoint from Hosts List.

#### 0.9.4
* Admin Settings: WordPress.org has been added as an API host option, and is the new default value.
* Admin Settings: A new admin bar menu has been added to display the current API host.
* Admin Settings: Admin notices now replace browser alerts when managing the log.
* Admin Settings: The "Clear Log" and "View Log" elements are now only visible when a log is known to exist.
* API Rewrite: A new AP_BYPASS_CACHE constant may be used to add cache busting to API requests.
* Branding: The branding notice is now permanently dismissible.
* Accessibility: The link in the branding notice now has more descriptive text.
* Accessibility: The "Generate API Key" button now has a visual label.
* Accessibility: The Voltron easter egg now uses a role of "img" with a label.
* Accessibility: The Voltron easter egg's animation now respects user motion preferences.
* Accessibility: Field labels and descriptions, and their associations, have been improved.
* Accessibility: The "View Log" popup has been removed, and the "View Log" button is now a link to the log file.
* Package: The "Tested up to" plugin header has now been set to WordPress 6.8.1.
* Package: Hosts data is now stored in a new hosts.json file in the plugin's root directory.
* Workflows: PHPUnit tests will now run against PHP 8.4.
* Workflows: End-to-end (E2E) tests now only run when manually triggered.

#### 0.9.3
* Compatibility: API rewrites now occur on a late hook priority.
* Compatibility: API rewriting can be optionally skipped if the request already has a response.
* Documentation: CHANGES.md is now used for the changelog instead of readme.txt.
* Documentation: The default AP_HOST value in README.md is now api.aspirecloud.net.
* Package: The dash in "aspire-update" has been removed from the package name.
* Dependencies: The translations-updater dependency has been updated to 1.2.1.

#### 0.9.2
* Package: The plugin's version has been updated.

#### 0.9.1
* First 0.9.x release because 0.9 was not properly versioned and tagged.

#### 0.9 (never released)
* New downloadable release for "Beta Soft Launch" - no changes from 0.6.2.

#### 0.6.2
TODO: WRITEME

#### 0.6.1
* Added AspireCloud.io endpoint for bleeding edge testing
* Added content type json header for better error retrieval from AC

#### 0.6
* Admin Settings: Added notices for when settings are saved or reset
* Branding: Added branded notices to inform users when AspireUpdate is in operation on a screen
* Multisite: Added multisite support
* Debug: Added Clear Logs and View Logs functionality
* I18N: Added Catalan translation
* I18N: Added Dutch translation
* I18N: Added Spanish translation
* I18N: Added Swedish translation
* I18N: Updated Dutch translation
* I18N: Updated French translation
* I18N: Updated German translation
* Testing: Added Git Updater integration
* Testing: Added support both main and playground-ready links in the README
* Testing: Made Playground default to the `main` branch
* Testing: Removed Hello Dolly from the Playground blueprint
* Security: Fixed Plugin Check security warnings

#### 0.5
* first stable version, connects to api.wordpress.org or an alternative AspireCloud repository
