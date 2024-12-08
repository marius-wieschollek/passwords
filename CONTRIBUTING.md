## Rules for contributors

* Git commits have to be [signed](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/Contributing/Verify-Git-Commits)
* Keep any discussions professional and civil

## Rules for issues

* Issues not following the template will be closed without review
* No "I have this issue"-comments. Use the 👍 reaction if you experience the same issue.
* Issues with the browser extension have to be opened in this [repository](https://github.com/marius-wieschollek/passwords-webextension)
* For questions and support, visit the [forum](https://help.nextcloud.com/tag/passwords-app)

## Set up the local environment
### Option 1: Development with the Docker environment
This repository ships with a full docker based development environment that enables you to use all app functionality without much setup.

#### Requirements
* Internet Connection
* [Node](https://nodejs.org/)
* [Docker](https://store.docker.com/search?type=edition&offering=community)
* [Docker Compose](https://docs.docker.com/compose/install/#install-compose)
* [mkcert](https://github.com/FiloSottile/mkcert)

#### Setup
1. Checkout the project with `git clone git@github.com:marius-wieschollek/passwords.git`
2. Open the project directory in a terminal
3. Run `npm ci`
4. Run `npm run build`
5. If you have [mkcert](https://github.com/FiloSottile/mkcert), run `npm run mkcert`
6. Run `npm run start`
7. Open your browser and type [https://localhost/](https://localhost/)
8. Log in with the username `admin` and the password `admin`
9. You can now access the [app](http://localhost/index.php/apps/passwords)


### Option 2: Development with a Nextcloud Server
You can start development on the passwords app with your existing Nextcloud installation.

#### Requirements
* Internet Connection
* [Node](https://nodejs.org/)

#### Setup
1. Checkout the project with `git clone git@github.com:marius-wieschollek/passwords.git`
2. Symlink the /src folder to the /passwords folder in the Nextcloud apps directory
   or set up a file synchronisation between the folders.
3. Run `npm install`
4. Run `npm run build`
5. Open the apps store in your Nextcloud and enable the Passwords app


### Sample Data
The docker setup comes with two test users `max` and `erika` with the password `PasswordsApp`.
All users have a set of sample data installed.
You can also download a [file with sample data](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/SamplePasswords.json) from the wiki and can import it on the [backups page](http://localhost/index.php/apps/passwords#/backup).

### API Debugging
We offer a [postman collection](https://git.mdns.eu/nextcloud/passwords/-/wikis/Developers/_files/postman-collection.json) for the API debugging tool [Postman](https://www.postman.com/).

### Helpful commands
* `npm run watch` - Create development build of JS/CSS files and watch for changes
* `npm run start` - Start the docker server. Use `postgres:start`, `sqlite:start`, `ldap:start` or `saml:start` to include these services
* `npm run stop` - Stop the docker server. Use `postgres:stop`, `sqlite:stop`, `ldap:stop` or `saml:stop` to include these services
* `npm run cron` - Execute cron jobs
* `npm run shell` - Open a shell in the container
* `npm run shell:root` - Open a root shell in the container
* `npm run down` - Remove docker containers and volumes. Use `postgres:down`, `sqlite:down`, `ldap:down` or `saml:down` to include these services
* `npm run docker:build` - Start docker and build images
* `npm run build` - Create production build of JS/CSS files
* `npm run build:nightly` - Create nightly build of JS/CSS files
* `npm run cypress` - Frontend testing and create screenshots for the manual
* `docker-compose -f docker-compose.yml -f docker/configs/<service>.yml up -d` starts the system with additional services
* `docker exec -u www-data passwords-php /var/www/html/occ  config:system:set loglevel --value=0 --type=int` - Set loglevel to info

### Services
 - [https://localhost](https://localhost) is Nextcloud (with the app)
 - [https://localhost:8080](https://localhost:8080) is PhpMyAdmin (for the MySQL database)
 - [https://localhost:1080](https://localhost:1080) is MailHog (for Emails)
 - [http://localhost:8081](http://localhost:8081) is pgAdmin (for the Postgres database, user `admin@passwords.local`, password `nextcloud`. The database password is also `nextcloud`)
 - [https://localhost:9090](https://localhost:9090) is PhpLdapAdmin (optional, user `cn=admin,dc=nextcloud`, password `nextcloud`)
 - [https://localhost:9091](https://localhost:9091) is Keycloak (for SAML, optional, user `admin`, password `nextcloud`)

### LDAP Setup
 - Open [PhpLdapAdmin](https://localhost:9090) and log in with `cn=admin,dc=nextcloud` as User-DN and `nextcloud` as password
 - Create a new entry with the template "Generic: Posix Group"
 - Create a new Entry with the template "Generic: User Account" as child entry of the group
 - Enable "LDAP user and group backend" in Nextcloud
 - Go to "Settings" > "LDAP/AD Integration"
 - Enter `openldap` as server and `389` as port
 - Enter `cn=admin,dc=nextcloud` as User-DN and `nextcloud` as password
 - Click on "Save Credentials"
 - Enter `dc=nextcloud` as Base DN
 - Click on "Continue"
 - Select "posixAccount" in "Object Classes"
 - Click on "Continue"
 - Select "cn" in "other attributes"
 - Click on "Continue"
 - Select "posixGroup" as object classes

### Telegram 2FA Gateway Setup
 - Install the "Two-Factor Gateway" app from the app store
 - Run `npm run shell` from the terminal
 - Run `./occ twofactorauth:gateway:configure telegram`
 - Enter `703713714:AAGUFZfIINIm4_0Qo3i9qHNZVfduDbwHtnY`
 - Chat with the [get_id_bot](tg://resolve?domain=get_id_bot) to get your Telegram id

#### Keycloak SAML Setup
 - Open [Keycloak](https://localhost:9091) and log in
 - Follow [this guide](https://janikvonrotz.ch/2020/04/21/configure-saml-authentication-for-nextcloud-with-keycloack/) ([internet archive link](https://web.archive.org/web/20231108192848/https://janikvonrotz.ch/2020/04/21/configure-saml-authentication-for-nextcloud-with-keycloack/))
   - Instead of `example.com` use `localhost`
   - Instead of `login.example.com` use `localhost:9091`
   - Don't forget to set a password for any created user

## More information
* [Developer Manual](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/Index)

## Translations
1. You can translate the app on our [Weblate](https://weblate.passwordsapp.org/projects/passwords/passwords/)
2. If you want to add a new language, make a [feature request](https://github.com/marius-wieschollek/passwords/issues/new/choose) and it will be added.