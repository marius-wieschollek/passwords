# Passwords
**A simple, yet feature rich password manager for Nextcloud**

## Contents
* [Features](#features)
* [Security](#security)
* [Apps](#apps)
* [API](#api)
* [Installation](#installation)
* [Development](#development)

## Features
#### Easy to use web interface
Manage your passwords with ease thanks to the modern and clear web interface provided in Nextcloud.
Keep your password collection manageable with folders and tags.

#### Modern password generator
The integrated password generator follows the new [NIST Digital Identity Guidelines](https://pages.nist.gov/800-63-3/).
It generates long passwords which are easy to remember, yet long and secure.
Say goodbye to pseudo random letters and numbers, random password requirements and contra productive expiration guidelines without reason.
Why? Because it is easier for you to remember that your twitter account unlocks with a "furiousToothbrush" than "fAu*j,4?rQ:25(#c".
Of course we know that most websites still require numbers and special characters, sou we will help you with that as well.

#### Integrated security check
**Soon to come:** Passwords can check automatically if your accounts or passwords have been compromised.
You can get convenient e-mail updates when the security status of a password has changed.
**Soon to come:** You can also define custom password rules to make sure all your passwords live up to your expectations.

#### Legacy API and APP Migration support
**Soon to come:** Are you using Fallon Turners Passwords app for Nextcloud/OwnCloud? No problem!
Passwords has a migration tool and will automatically import all the passwords on your Nextcloud instance.
Thanks to the legacy API support you can even keep applications using the old API.
(But passwords with client side encryption are not available on the old API)

#### Build upon modern standards
Passwords is fast and uses modern web technology to give you the best experience possible.
Rather than implementing own and possibly error-prone encryption algorithms Passwords uses standardized and well checked APIs to encrypt your passwords.
**Soon to come:** The web interface is mobile friendly and can be used with any modern web browser.

#### Wide language support
**Soon to come:** Passwords is available in english, german, french, spanish, italian, dutch and polish.
All thanks to the best in class automatic translations form [DeepL](https://www.deepl.com/translator).

## Security
#### Server Side Encryption
Passwords offers a safe server side encryption using Nextclouds integrated encryption functionality.
Each password is encrypted with it's own unique key.
**Soon to come:** A master password will be available for additional security.

#### Client Side Encryption
**Soon to come:** Using the [Web Crypto API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Crypto_API), you have the ability to choose a strong client side encryption for your passwords.

## Apps
There are no apps for the modern API right now. But there are some for the legacy API.

* [Firefox Add-On](https://addons.mozilla.org/en-US/firefox/addon/firefox-owncloud-passwords/)
* [Chrome Extension](https://github.com/thefirstofthe300/ownCloud-Passwords)
* [Android App](https://github.com/intirix/cloudpasswordmanager)

## API
Passwords offers complete API, giving you access to all features of the app.
**Soon to come:** Documentation and JavaScript Example Implementation will be available soon

## Installation
**Soon to come:** Simply install the app from the [Nextcloud App Store](https://apps.nextcloud.com/).

#### Requirements
* Nextcloud 12+
* PHP 7.0+
* openssl

## Development
**Soon to come:**
* Do you want to help us? Read the [Guide](CONTRIBUTING.md) and get started.
* Do you want do want to use the API? Read the [docs](https://github.com/marius-wieschollek/passwords/wiki).