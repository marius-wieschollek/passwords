## [Unreleased]
## 2025.10.0 - Feature release
### Added
- Improved performance of sharing job
### Fixed
- NC 32 / PHP 8.4 dependency injection issues
- Crash in impersonation notification email


## 2025.9.0 - Feature release
### Added
- Nextcloud 32 support

## 2025.7.0 - Feature release
### Added
- Server secret for app only
### Fixed
- Password label from website name not working for IPs

## 2025.5.1 - Bugfix release
### Fixed
- Issues with LSR release

## 2025.5.0 - Feature release
### Fixed
- Error messages when using PHP 8.4

## 2025.4.0 - Feature release
### Fixed
- Error messages when using PHP 8.4
- E2E passphrase not working on dashboard
### Changed
- Updated breached passwords list

## 2025.2.1 - Bugfix release
### Fixed
- Login not working in dashboard widget

## 2025.2.0 - Feature release
### Removed
- Support for PHP 8.2
- Support for Nextcloud 28 & 29

## 2024.12.1 - Bugfix release
### Fixed
- Fixed version number in EOL message

## 2024.12.0 - Feature release
### Added
- Traditional Chinese translation
### Changed
- Updated Dashlane import to new format
### Fixed
- Crash on requests with bearer auth
- Favicons requests result in 429 error code


## 2024.11.0 - Feature release
#### Fixed
- Sharing not working
- User verification fails on NC 30.0.2 and NC 29.0.9

## 2024.9.0 - Feature release
### Added
- Translation for Galician
### Changed
- Removed some special characters from password generator
### Fixed
- Imaginary not working when api key used

## 2024.7.0 - Feature release
### Changed
- Breached passwords database updated to v12
- Info page when WebAssembly disabled
### Fixed
- UTF-8 characters in user names not working
- Migration not working with ancient database schema

## 2024.5.0 - Feature release
### Changed
- CSP no longer needs eval
- Breached passwords database updated to v11

## 2024.4.1 - Bugfix release
### Fixed
- Local breached passwords db fetched every day instead of once


## 2024.4.0 - Feature release
### Added
- Translation for Arabic
### Changed
- Handling of expired sessions improved
### Fixed
- Bitwarden import error when custom field has no label
- Imaginary error when resizing images
- Error when caching preview & favicons
- Search input becomes laggy after some time


## 2024.3.0 - Feature release
### Changed
- "More" menu layout
- Added Ctrl + F to search again
### Fixed
- Handling of custom data fields in the editor
- Tags missing on edit as new
- Invalid URLs from Bitwarden import
- First run wizard style
- Sharing E2EE passwords fails


## 2024.2.0 - Feature release
### Added
- Improved accessibility features
- Setup checks for common issues
### Fixed
- Impersonate not working with LDAP users


## 2024.1.1 - Bugfix release
### Fixed
- PassLink not working
- Sharing takes longer than usual

## 2024.1.0 - Feature release
### Added
- In-App search
- Breached password hashes API
- Encryption passphrase security check
### Changed
- Breached passwords database updated to v10
- "pwned-list:process" command now uses the new HIPB format
- Authentication page now automatically focuses password field
### Removed
- Support for Nextcloud 25, 26, 27
- Support for PHP 7.4, 8.0, 8.1
- Diagrams missing in handbook

## 2023.12.2 - Bugfix release
### Fixed
- Excessive session creation
- Admin settings don't save in NC 28
- Preview services crash on error
- Bitwarden import crashes when no folders present

## 2023.12.1 - Bugfix release
### Changed
- EOL notification appears only once per month
### Fixed
- Dashboard widget crash in NC 25

## 2023.12.0 - Feature release
### Added
- Dashboard widget
- Imaginary support
### Changed
- Switched to NC 28 design components
- Updated request scheduler to improve performance
- Fallback icon handling
- Handbook & Appstore design update
### Fixed
- Leipzig Corpora API upgraded

## 2023.11.0 - Feature release
### Added
- NC 28 compatibility
- PHP 8.3 compatibility
- Log in with WebAuthn
### Fixed
- App crash after user password change
- Invalid tokens created SAML users which expire after 5 minutes
- Token permissions trigger mount point error in files_external
- Layout issues on printouts
- Session timeout message not showing up

## 2023.10.0 - Feature release
### Fixed
- Shared passwords can't be moved if not editable
- Server crash instead of validation error
- Server crash when user id is invalid

## 2023.9.0 - Feature release
### Added
- Added translation for Norwegian Bokmål
### Fixed
- Endless loop in shared password synchronization
- Error in website preview api

## 2023.8.1 - Bugfix release
### Fixed
- Language level error in NC25 PHP 7.4 LSR

## 2023.8.0 - Feature release
### Fixed
- Cookie based api session not working
- Wrong background image in login screens
### Changed
- Improved SSEv2 security

## 2023.7.0 - Feature release
### Fixed
- Invalid version for LSR release
- Sharing not working if defective share entry exists
- Pressing enter in password dialog saves and closes dialog

## 2023.6.0 - Feature release
### Added
- Initial NC 27 Support

## 2023.5.0 - Feature release
### Changed
- Navigation toggle always visible
- Removed start page of setup wizard
### Fixed
- Creating tag in password edit dialog fails when no tags exist
- Incorrect link to broken passwords section in navigation
- Incorrect links to user background images in NC 26

## 2023.4.0 - Feature release
### Fixed
- 404 error in passlink connect dialog
- PHP 8 incompatibility when using NC 26

## 2023.3.1 - Bugfix release
### Fixed
- App not loading with NC26
- Invalid custom fields cause database import to fail

## 2023.3.0 - Feature release
### Added
- Open change password page directly
### Fixed
- Password edit form shows old data

## 2023.2.2 - Bugfix release
### Fixed
- E2EE broken by libsodium update

## 2023.2.1 - Bugfix release
### Fixed
- Autoloader not registered during update
- Hidden passwords not unhidden when moved
- Big local password database is downloaded every day

## 2023.2.0 - Feature release
### Added
- SSEv3 with third party encryption key
- Initial NC 26 Support
- Initial PHP 8.2 Support
### Changed
- Copy to clipboard notification informs if copied property is empty
- API request behaviour changed to improve responsiveness
### Fixed
- Some UI glitches

## 2023.1.3 - Bugfix release
### Fixed
- Change encryption password doesn't save
- Security status missing in list view
- Help section may be misaligned
- User theme colors not applied in navigation

## 2023.1.2 - Bugfix release
### Fixed
- PHP 8.1 warning for PHP 8 users
- Nextcloud header navigation background color
- SQL error in sharing job

## 2023.1.1 - Bugfix release
### Fixed
- Search not showing results

## 2023.1.0 - Feature release
### Added
- User migration support
- Print password option
- Navigation quick access for favorites, folders, tags, shared and security
- Adding tags in password create and edit dialogs
- Simple view for password details
### Changed
- Breadcrumb now shows full path
- Migrated to Nextcloud Vue components for better integration
### Fixed
- Broken help link in import section
### Removed
- NC 23 & 24 support

## 2022.12.1 - Bugfix release
### Fixed
- Data loss caused by NC 25 secret migration
- Platform deprecation notification potentially sent more than once

## 2022.12.0 - Feature release
### Added
- Translations for Danish and Ukrainian
### Changed
- Updated translations for Dutch, Spanish, French, Swedish and Italian

## 2022.11.1 - Bugfix release
### Fixed
- Nextcloud 25 styling issues
- Navigation in Handbook not working in Nextcloud 25

## 2022.11.0 - Feature release
### Added
- Nextcloud 25 Support
- Dialog to restore hidden items
- Portuguese language
### Changed
- Lowered automatic performance settings for SQLite
- Updated translations
### Fixed
- Some missing translations

## 2022.10.0 - Feature release
### Changed
- Updated translations
### Fixed
- Disabled users no longer suggested for sharing

## 2022.9.0 - Feature release
### Added
- Mousewheel click action for password list items
- Customizable button for password list items
### Fixed
- Some missing translations

## 2022.8.0 - Feature release
### Changed
- Markdown editor switched to ToastUI Editor
- Markdown renderer upgraded
- Self-hosted Screeenly instances now allow HTTP
- Notifications now use full image url for icon
- Replaced external random color generator
### Fixed
- Deprecated variable error in PHP 8.1

## 2022.6.0 - Feature release
### Fixed
- Support notification uses incorrect NC version

## 2022.5.0 - Feature release
### Fixed
- Version check in nightly updater

## 2022.4.0 - Feature release
### Added
- Support for Nextcloud 24
- Support for PHP 8.1
### Fixed
- Bitwarden import crashes on error

## 2022.3.0 - Feature release
### Changed
- Improved HTTPS setup report
### Fixed
- Export can not handle invalid custom fields

## 2022.2.2 - Bugfix release
### Fixed
- Invalid certificate in signature
- Broken delete statement if table entries from ocPasswords found

## 2022.2.1 - Bugfix release
### Fixed
- Migration is missing createNamedParameter
- Language level incompatibilities in LSR

## 2022.2.0 - Feature release
### Added
- Auto selection for words service
### Changed
- Improved PHP version mismatch warnings
- Simplified system requirement warnings
### Fixed
- Background migration does not upgrade SSEv1r1
- Failed NC encryption api response ignored
- Passwords security service settings missing from backup
- ocPasswords tables cause table migration to fail
- Password security status not updated in ui

## 2022.1.0 - Feature release
### Changed
- Big local database now with 25M passwords from Hibp?
- Small local database now with 5M passwords from Hibp?
- Security status now marked as unknown if hash disabled
### Fixed
- Passwords now unhidden when moved out of hidden folder
### Removed
- The legacy api
- Support for Nextcloud 20, 21, 22
- Support for PHP 7.4 (7.2 in LSR)

## 2021.12.0 - Feature release
### Added
- Nextcloud 23 support
- Added translation for catalan
### Changed
- Improved spanish translation
- Default besticon instance, removed limitations
- Passlink domains

## 2021.11.0 - Feature release
### Changed
- Updated wording for too many decryption attempts
### Fixed
- Password creation fails when e2e and custom hash length used
- Edited time of shared passwords not synchronized
- Bitwarden CSV import mapping incorrect
- Bitwarden JSON import crashing when required fields are missing
- Fixed issues with LastPass

## 2021.10.0 - Feature release
### Changed
- System requirement notice
### Fixed
- Navigation icons in NC21
- Backup restore setting not working

## 2021.9.0 - Feature release
### Added
- Cache folders now marked with CACHEDIR.TAG file
### Changed
- Improved Czech translation
### Fixed
- Security hash admin setting shown incorrectly
- Navigation icons in NC22

## 2021.7.3 - Bugfix release
### Added
- Automatic backup restore
### Changed
- Default Backup keep time

## 2021.7.2 - Bugfix release
### Fixed
- Passwords marked as duplicate when no hash stored
- Database migration fails if data partially migrated
- Incorrect auto_increment values in PostgreSQL
- Not editable passwords can be shared as editable

## 2021.7.1 - Bugfix release
### Fixed
- Index naming issue on PostgreSQL
- Attempt to migrate data from not existing tables

## 2021.7.0 - Feature release
### Added
- Nextcloud 22 support
- Legacy support releases for PHP 7.2
- Add support for partial hashes
### Changed
- All table names
### Fixed
- Bitwarden import fails when credit card fields empty

## 2021.6.0 - Feature release
### Added
- Legacy support releases for PHP 7.3
- Depreaction announcement for NC 20 & PHP 7.4
### Changed
- Autocreated encryption password entry now has description
### Fixed
- Password api does not check if password property is present

## 2021.5.0 - Feature release
### Added
- Warning for sharing qr code
- Allow unhiding of hidden passwords in edit form

## 2021.4.0 - Feature release
### Added
- New password form
- Move functionality for passwords and folders
- Right click menu
- Translation for pt_BR

## 2021.3.0 - Feature release
### Added
- PHP 8.0 support
### Changed
- Details tab styling for NC21
### Fixed
- Encryption setup password length check

## 2021.2.0 - Feature release
### Added
- PassLink connect by link for chrome
- Firefox Lockwise import profile
- Warning in admin settings if cron uses different PHP version
- Account reset for password-less accounts
- Listing for iOS app from johannes-schliephake
### Changed
- Apps & Extensions section design
- Improved PassLink QrCode
### Fixed
- HTTPS report causes crash
### Removed
- Encryption feature switches

## 2021.1.3 - Bugfix release
### Fixed
- SSEv2 initialization error

## 2021.1.2 - Bugfix release
### Removed
- Nextcloud minimum version check

## 2021.1.1 - Bugfix release
### Fixed
- Invalid type in custom fields
- Bad contrast on QR codes
- Backup password field remains readonly
- Skip broken tags in Passman import

## 2021.1.0 - Feature release
### Added
- Nextcloud 21 support
- System report command
### Changed
- Feature management now within NC
- Legacy api now disabled by default
### Fixed
- Nightly app fetcher ignores PHP requirement
### Removed
- Nextcloud 17, 18 and 19 support
- PHP 7.2 and PHP 7.3 support

## 2020.12.4 - Bugfix release
- Password api does not check if password property is present
- CSE can be used by client when not enabled

## 2020.12.3 - Bugfix release
### Fixed
- Recursion in folder path
- E2E password length requirement
- Handbook url
- Invalid custom fields array
- Low strength password generation
- KeePassX import mapping issue
- Error messages caused by expired PassLink request
- Outdated besticon guide url

## 2020.12.2 - Bugfix release
### Fixed
- Invalid type in custom fields
- Bad contrast on QR codes
- Backup password field remains readonly
- Skip broken tags in Passman import

## 2020.12.1 - Bugfix release
### Changed
- Improved swedish translations
### Fixed
- Fix search results missing when details opened
- Fix broken temp directory path
- Fix crash when user token is missing
- Fix missing app description translations
- Fix missing email and notification translations
- Fix timeout in admin settings
- Fix key generation for encryption password change

## 2020.12.0 - Feature release
### Added
- Configurable default options for sharing
- Highlight password entry with details open
### Changed
- API always returns current revision id
### Fixed
- Security section fails to load passwords
- Search hotkey missing in NC20

## 2020.11.1 - Bugfix release
### Fixed
- wo4snakes connection issue
- Notifications don't have links in NC 20
- Unclear EOL notification

## 2020.11.0 - Feature release
### Changed
- Local words quality improved
- Improved browser compatibility warnings
- Ansible integration app by markuman
### Fixed
- BC break warning in app settings not shown

## 2020.10.0 - Feature release
### Added
- Android app by joleaf
### Changed
- Minimum requirement notification now for 2021.1.0 requirements
- Spanish translation improved
### Fixed
- Shared password can not be deleted

## 2020.9.0 - Feature release
### Added
- Nextcloud 20 support
### Changed
- Migrated favicon services to Nextcloud http client api
- Migrated preview services to Nextcloud http client api
- Migrated words services to Nextcloud http client api
- Migrated security check services to Nextcloud http client api
- Import respects server performance preference
- E2E migration respects server performance preference
### Fixed
- Backup restore always restores config for all users
- Broken HTTP fallback in pageres cli preview generator
- Client attempts to use cached session tokens

## 2020.8.0 - Feature release
### Added
- Asynchronous loading of favicons
- List programming resources in apps section
### Changed
- List of recommended browsers

## 2020.7.0 - Feature release
### Added
- Italian translation

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