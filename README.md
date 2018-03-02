# Passwords
> Easy to use yet feature-rich and secure password manager for Nextcloud

## Contents
* [Features](#features)
* [Security](#security)
* [Apps](#apps)
* [API](#api)
* [Installation](#installation)
* [Development](#development)
* [Licenses](Licenses.md)

## Features
#### Easy to use web interface
Manage your passwords with ease in Nextcloud thanks to the modern and clear web interface provided.
Keep your password database manageable and accessible with folders and tags.

#### Modern password generator
The integrated password generator follows the new [NIST Digital Identity Guidelines](https://pages.nist.gov/800-63-3/).
It generates long passwords which are easy to remember, yet long and secure.
Say goodbye to pseudo random letters and numbers, random password requirements and contra productive expiration guidelines without reason.
Why? Because it is easier for you to remember that your twitter account unlocks with a "FuriousToothbrush" than "fAu*j,4?rQ:25(#c".
Of course we know that most websites still require numbers and special characters, sou we will help you with that as well.

#### Integrated security check
Passwords like "P@55w0rd" may fool most generic security checks, but they are so common that most hackers have them in their lists.
This is why passwords checks automatically if your passwords have been compromised by integrating the [Have I been pwned](https://haveibeenpwned.com/) service.
**Soon to come:** You can get convenient e-mail updates when the security status of a password has changed.
**Soon to come:** You can also define custom password rules to make sure all your passwords live up to your expectations.

#### Legacy API and APP Migration support
Are you using Fallon Turners Passwords app for Nextcloud/OwnCloud? No problem!
Passwords will automatically import all the passwords from the old app.
Take a look at the [legacy migration guide](https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Legacy-Migration) for more details.
Thanks to the legacy API support you can even keep applications using the old API.
(But passwords with client side encryption are not available on the old API)

#### Build upon modern standards
Passwords is fast and uses modern web technology to give you the best experience possible.
Rather than implementing own and possibly error-prone encryption algorithms Passwords uses standardized and well checked APIs to encrypt your passwords.
The web interface is mobile friendly and can be used with any modern web browser.

#### Wide language support
**Soon to come:** Passwords is available in english, german, french, spanish, italian, dutch and polish.
All thanks to the best in class automatic translations form [DeepL](https://www.deepl.com/translator).

## Security
#### Server Side Encryption
Passwords offers a safe server side encryption using Nextclouds integrated encryption functionality.
Each password is encrypted with it's own unique key.
**Soon to come:** A master password will be available for additional security.

#### Client Side Encryption
**Soon to come:** Passwords supports a strong client side encryption for your passwords.

## Apps
There are no apps for the modern API right now. But there are some for the legacy API.
* [Official Firefox Extension](https://addons.mozilla.org/firefox/addon/nextcloud-passwords/)
* [Official Chrome Extension](https://github.com/marius-wieschollek/passwords-webextension/wiki/chromium-builds)
* [Android App](https://play.google.com/store/apps/details?id=com.intirix.cloudpasswordmanager) by [Intirix](https://github.com/intirix/cloudpasswordmanager)

## API
Passwords offers complete API, giving you access to all features of the app.
**Soon to come:** Documentation and JavaScript Example Implementation will be available soon

## Installation
Simply install the app from the [Nextcloud App Store](https://apps.nextcloud.com/apps/passwords).

#### Requirements
* Linux based OS
* Nextcloud 12+
* PHP 7.1+

## Development
* Do you want to help us? Read the [guide](CONTRIBUTING.md) and get started.
* Do you want do want to use the API? Read the [docs](https://git.mdns.eu/nextcloud/passwords/wikis/home#developers).

## Licenses and Legal Information
See [Licenses.md](Licenses.md)