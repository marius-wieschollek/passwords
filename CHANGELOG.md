## [Unreleased]

## 2020.6.0 - Feature release
### Added
 - Edit password as new
 - EOL warning for NC < 19
 - EOL warning for PHP < 7.4
### Changed
 - Styling in NC 19
 - Removed Link option in PassLink for Chrome
 - HTTPS detection now removes client side flag automatically
 - Reduced background tasks
 - Improved QR code compatibility
### Fixed
 - Missing parent folder id
 - Incorrect sorting

## 2020.5.0 - Feature release
### Added
 - Nextcloud 19 support
 - Chrome / Google Passwords import
 - Subtitles in handbook videos
 - PassLink Connect setup for extensions
### Changed
 - Add/Update password now scans for duplicates
### Fixed
 - Email validation to strict

## 2020.4.1 - Bugfix release
### Fixed
 - Generating passwords with special characters fails often
 - Passwords causes error in Talk when impersonating user
 - Ajax and webcron do not work when installed in subfolder
 - Disabling server survey resets the setting

## 2020.4.0 - Feature release
### Added
 - Remote feature activation can be disabled
 - App upgrade alert for outdated versions
### Fixed
 - Dark theme detection
 - Broken https setup link

## 2020.3.1 - Bugfix release
### Fixed
 - Missing default value for login_type

## 2020.3.0 - Feature release
### Added
 - User delete cli command
 - User move cli command
### Changed
 - 2FA only required if master password is set
### Fixed
 - Database repair does not attempt to fix custom fields

## 2020.2.1 - Bugfix release
### Fixed
 - App crashes when social login used

## 2020.2.0 - Feature release
### Added
 - Spanish translation
 - Chinese translation
 - Database indexes
 - Performance improvements
### Changed
 - User info now fetched from session
 - Client side encryption encoding
### Removed
 - Nextcloud 15 database layer
### Fixed
 - Cron jobs not executed if NC installed in subdirectory

## 2020.1.0 - Feature release
### Added
 - Nextcloud 18 support
 - Bitwarden JSON import
 - Bitwarden CSV import
 - Leipzig Corpora random words service
### Changed
 - Enpass JSON now translates folder names
 - User search in sharing now has less throttling
### Fixed
 - Details view now closes when password is deleted from trash
### Removed
 - Nextcloud 15 and 16 support
 - PHP 7.1 support
 - Legacy ocPasswords migration
 - Database duplicate deletion
 - Backup migration

## 2019.12.1 - Bugfix release
### Fixed
 - Encryption setup does not encrypt existing entities
 - Clean up step in encryption setup always fails
 - Search globally with enter uses old query

## 2019.12.0 - Feature release
### Added
 - Lazy loading for favicons and avatars
### Fixed
 - Server timeout on settings page
 - Mobile Layout in NC 17
 - Navigation in NC 15
 - Attempt to send mails to users without email

## 2019.11.1 - Bugfix release
### Fixed
 - Local dictionary password generator issues

## 2019.11.0 - Feature release
### Added
 - Nextcloud outgoing proxy support
### Changed
 - Report notification not showing if app not used
 - Words services now produce better passwords

## 2019.10.0 - Feature release
### Added
 - EOL Notification for NC 15, 16 and PHP 7.1
 - Opt-Out for admin notifications
### Changed
 - Http issues are now logged

## 2019.9.1 - Bugfix release
### Fixed
 - Navigation in Nextcloud 16.0.5

## 2019.9.0 - Feature release
### Added
 - Nextcloud 17 support
 - Password duplicate search
 - Check for incomplete service configuration
 - Nextcloud code signing
### Changed
 - Virtual guest group from guests app now ignored in autocomplete
 - Search field can now be reset with escape
### Fixed
 - User name input not shown in sharing tab
 - Import ignores client side encryption settings
 - Backup does not reset some user settings
 - Sharing not possible if autocomplete disabled
 - Some fields do not work in search

## 2019.8.1 - Bugfix release
### Changed
 - Login screen ignores missing meta tags
### Fixed
  - "Hide users from guests" applied to all users
  - Some integrations not reported in server survey

## 2019.8.0 - Feature release
### Added
 - Sharing now available from menu
 - Support user hiding in guests app
 - Backup Import/Export
 - SafeInCloud CSV import
 - Line break option for custom CSV
### Changed
 - Login screen now skipped entirely if not needed
 - CLI now allows custom backup names
### Fixed
 - Correct handling of empty files in import
 - Display file reading errors in import
 - Respect bad quotes setting in import

## 2019.7.2 - Bugfix release
### Fixed
 - Migration does not create uuids for deleted items
 - Database cleanup fails with entries without uuid
 - Tags can not be created
 - Master password can not be changed
 - Corrupted revisions are not removed by database repair
 - Legacy API initializes custom fields wrong

## 2019.7.1 - Bugfix release
### Fixed
 - Upgrade fails with PostgreSQL
 - App crash when auth header missing
 - Empty section in settings
 - Session keep alive not working
 - Pageres dos not work with https only

## 2019.7.0 - Feature release
### Added
 - Screeenly website previews
 - Pageres 5.x support
 - Admin impersonation
 - Revision view shows name of the client
 - Improved SSEv1 variant
 - Server survey
 - Client-Side-Encrption (not active)
 - Strong Server-Side-Encryption (not active)
 - Two-Factor-Authentication support (not active)
 - Tutorial (not active)
### Fixed
 - Permanent object removal ignoring settings
 - Enpass folder import

## 2019.6.0 - Feature release
### Changed
 - Compress language files and plain js
### Fixed
 - BigDB+Hibp database update not initiated
 - Cache folder not created before write
 - Dirty data in custom fields

## 2019.5.0 - Feature release
### Added
 - Swedish translation
 - Enpass Json Import
 - Roboform CSV Import
 - Custom fields support in export & import
 - Password revision detail view
 - Password names in legacy api
 - screenshotlayer.com preview service
 - Browshot preview service
 - Changelog in app store
### Changed
 - Improved Passman import (JSON & CSV)
 - Improved help section
 - Improved sharing ui
 - Improved mobile ui
### Fixed
 - Editable status of shares of shared password not updated
### Removed
 - screenshotapi.io preview service

## 2019.4.2 - Bugfix release
### Fixed
 - Passman csv import
 - Passman JSON import with empty passwords

## 2019.4.1 - Bugfix release
### Fixed
 - Invalid font file path in icon generator
 - Spelling mistake in french e-mail
 - Incorrect version in backups

## 2019.4.0 - Feature release
### Added
- Nextcloud 16 support
### Changed
- Improved custom fields
- Improved autofill prevention in chrome
### Fixed
- Double SSE key generation
- Tags being not removed from passwords

## 2019.3.0 - Feature release
### Changed
- Improved legacy api password update
- Improved import performance
### Fixed
- Deleting shared passwords issue

## 2019.2.2 - Bugfix release
### Fixed
- Broken user login name detection for LDAP users
- Invalid default font path

## 2019.2.1 - Bugfix release
### Fixed
- Broken favorite button
- PHP notice in admin settings
### Security
- Fixed user name confusion in environment service

## 2019.2.0 - Feature release
### Added
- Edit/Nothing onclick actions for password
- Setting to disable website preview
### Changed
- Improved https debugging report
### Fixed
- Copy username menu
- Continuous HTTP 401 requests
- Dictionary detection

## 2019.1.0 - Feature release
### Added
- Nextcloud 15 support
- Dark Theme support
### Changed
- Improved automatic words service choice
- Improved nightly update functionality
### Fixed
- ImageMagick detection issue
- Editable status check in legacy api
- Changedate issues in legacy api
### Removed
- Nextcloud 12, 13 and 14 support

## 2018.12.0 - Feature release
### Added
- Apps & extensions section
### Changed
- Improved shared password synchronisation
- Improved password generator
- Improved nightly updates
### Fixed
- Instant search in chromium browsers
- Server side validation in password generator

## 2018.11.2 - Bugfix release
### Fixed
- Copy to clipboard
- Environment Service in cli mode
- Invalid security status code
- Drag and drop scrolling not working
- Search link in footer not working

## 2018.11.1 - Bugfix release
### Changed
- Improved french translation
### Fixed
- Copy to clipboard on iOS
- Deleting of empty tokens
- Empty user name issues

## 2018.11.0 - Feature release
### Added
- Server side backups
- French translation
- One time api tokens
### Changed
- Improved admin settings save feedback

## 2018.10.0 - Feature release
### Added
- Dutch translation
- Russian translation
### Changed
- Improved background job logging
### Fixed
- Errors in admin settings using http

## 2018.9.1 - Bugfix Release
### Fixed
- Custom fields initialization migration
- Api token reset in Nextcloud 14
- Sharing with autocomplete disabled
- Layout issues in Nextcloud 14

## 2018.9.0 - Feature Release
### Added
- Nextcloud 14 Support

## 2018.7.0 - Feature Release
### Added
- User password rules
- Haveibeenpwned V2 support
- Database repair
### Changed
- Improved search
- Improved import
- Improved custom fields editor
### Fixed
- Missing password in session token

## 2018.6.3 - Bugfix Release
### Fixed
- Codebase incompatibility in appstore update

## 2018.6.0 - Feature Release
### Added
- Czech translation
- Ajax cronjob support
### Fixed
- Invalid HIBP check results
- Custom fields upgrade
- Password update error in legacy api

## 2018.5.2 - Bugfix Release
### Fixed
- Password creation error in the legacy api

## 2018.5.1 - Bugfix Release
### Fixed
- Nextcloud 12 migration step issue
- Some css styling issues

## 2018.5.0 - Feature Release
- Added custom fields
- Added global search
- Added advanced settings
- Added user name in list view
- Added configurable click actions
- Added email default settings
### Changed
- Improved password title
- Improved password details
- Improved breadcrumb

## 2018.4.2 - Bugfix Release
### Fixed
- Reporting of password generator settings
- Sharing issues with LDAP

## 2018.4.1 - Bugfix Release
### Fixed
- Syntax error in index.php

## 2018.4.0 - Feature Release
### Added
- User handbook
- In page search
- Email alerts
- Notification settings
- Tags in the list view
- Configurable default section
- Start over option
### Changed
- Improved import and export
- Improved password generator
- Improved mobile style
- Improved loading times
### Fixed
- Folder view in trash
- Mime type detection
- Double call when opening folders

## 2018.3.0 - Feature Release
### Added
- User settings
- Delete all option in trash
- Custom besticon api url
- Sorting support
- DocX and ODS export
### Changed
- Improved password import
- Improved admin settings
- Improved sharing ui
- Improved loading times and data usage
- Improved password notification
- Improved image rendering
### Fixed
- Https detection issues
- BaseUrl issues
- Custom csv import issues
- Several ui glitches

## 2018.2.1 - Bugfix Release
### Fixed
- Share deletion error
- Password check running too often

## 2018.2.0 - Feature Release
### Added
- Added backup page
### Changed
- Improved admin settings ui
### Fixed
- SQLite issues
- Password edit ui issues

## 2018.1.1 - Initial Release
### Added
- Password management capabilities
- Folder capabilities
- Tag capabilities
- Share capabilities
- Integrated password checks
- New API
- Legacy migration
- Legacy api
- CORS support

## 2018.1 - Alpha Release
### Added
- First alpha release