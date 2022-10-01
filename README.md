# Passwords
> Easy to use yet feature-rich and secure password manager for Nextcloud

## Contents
* [Support](#support)
* [Features](#features)
* [Security](#security)
* [Apps](#apps)
* [API](#api)
* [Installation](#installation)
* [Development](#development)
* [Licenses](Licenses.md)

## Support
* [Official Chat](https://t.me/nc_passwords)
* [Official Forum](https://help.nextcloud.com/c/apps/passwords)
* [User Handbook](https://git.mdns.eu/nextcloud/passwords/wikis/Users/Index)
* [Administrator Handbook](https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Index)
* [Bug Tracker](https://github.com/marius-wieschollek/passwords/issues)

## Features
#### Easy to use web interface
Manage your passwords with ease in Nextcloud thanks to the modern and clear web interface provided.
Keep your password database manageable and accessible with folders and tags.

#### Modern password generator
The integrated password generator follows the new [NIST Digital Identity Guidelines](https://pages.nist.gov/800-63-3/).
It generates long passwords which are easy to remember, yet long and secure.
Say goodbye to pseudo random letters and numbers, random password requirements and contra productive expiration guidelines without reason.
Why? Because it is easier for you to remember that your twitter account unlocks with a "FuriousToothbrush" than "fAu*j,4?rQ:25(#c".
Of course we know that most websites still require numbers and special characters, so we will help you with that as well.

#### Integrated security check
Passwords like "P@55w0rd" may fool most generic security checks, but they are so common that most hackers have them in their lists.
This is why passwords checks automatically if your passwords have been compromised by integrating the [Have I been pwned](https://haveibeenpwned.com/) service.
You can get convenient e-mail alerts when one of your passwords has been compromised.
You can activate additional security checks to make sure your passwords fulfill your standards.

#### Build upon modern standards
Passwords is fast and uses modern web technology to give you the best experience possible.
Rather than implementing own and possibly error-prone encryption algorithms Passwords uses standardized and well tested APIs to encrypt your passwords.
The web interface is mobile friendly and can be used with any modern web browser.

#### Wide language support
Passwords is available in english, german, french, spanish, italian, dutch, swedish, czech, russian and chinese640.

## Security
#### Server Side Encryption
Passwords offers a safe server side encryption using Nextclouds integrated encryption functionality.
Each password is encrypted with it's own unique key.

#### Client Side Encryption (E2E)
If enabled on your server, you can have your passwords encrypted in your browser.
This will protect them from unauthorized access and even keep them safe if the server is hacked.

#### Access Protection & Two-Factor-Authentication
Passwords offers the option of a master password for additional safety. (In combination with client side encryption)
The app also supports most 2FA authentication methods offered by Nextcloud.

## Apps

* [Official Firefox Add-On](https://addons.mozilla.org/firefox/addon/nextcloud-passwords?src=external-readme)
* [Official Chrome Extension](https://chrome.google.com/webstore/detail/nextcloud-passwords/mhajlicjhgoofheldnmollgbgjheenbi)
* [Official Microsoft Edge Extension](https://microsoftedge.microsoft.com/addons/detail/cjmlcljdpphgdfpkngjcmeepdpcgpffk)
* [Android App](https://play.google.com/store/apps/details?id=de.jbservices.nc_passwords_app) by [joleaf](https://gitlab.com/joleaf/nc-passwords-app)
* [Windows App (WIP)](https://www.microsoft.com/store/apps/9NXVZ0ZP6D5Z) by [j0chn](https://gitlab.com/j0chn/nextcloud_password_client)
* [Linux App (WIP)](https://gitlab.com/j0chn/nextcloud_password_client/-/tree/main#linux) by [j0chn](https://gitlab.com/j0chn/nextcloud_password_client)
* [macOS App (WIP)](https://gitlab.com/j0chn/nextcloud_password_client/-/tree/main#mac-os) by [j0chn](https://gitlab.com/j0chn/nextcloud_password_client)
* [iOS App](https://apps.apple.com/app/id1546212226) by [Johannes Schliephake](https://github.com/johannes-schliephake/nextcloud-passwords-ios)

## API
Passwords offers complete API, giving you access to all features of the app.
You can use our [javascript client](https://www.npmjs.com/package/passwords-client) or take a look at the [api documentation](https://git.mdns.eu/nextcloud/passwords/wikis/developers/index).

## Installation
Simply install the app from the [Nextcloud App Store](https://apps.nextcloud.com/apps/passwords).

#### Requirements
* Linux OS
* Nextcloud ([latest](https://nextcloud.com/install/#instructions-server))
* PHP (with [active support](https://www.php.net/supported-versions.php))
* Database (MariaDB, MySQL, PostgreSQL, Sqlite)
* [Complete list of requirements](https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/System-Requirements)

## Development
* Install our [nightly build](https://git.mdns.eu/nextcloud/passwords/-/jobs/artifacts/testing/raw/passwords.tar.gz?job=Pack).
* Do you want to help us? Read the [guide](CONTRIBUTING.md) and get started.
* Do you want to translate the app? You can do so on [Weblate](https://weblate.passwordsapp.org/projects/passwords/passwords/).
* Do you want to use the API? Read the [docs](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/Index).
* For questions and announcements, visit the [Telegram Group for Developers](https://t.me/nc_passwords_dev)

## Donations
See [Donate.md](Donate.md) for a list of developers making the Passwords app for Nextcloud possible.

## Licenses and Legal Information
See [Licenses.md](Licenses.md)
