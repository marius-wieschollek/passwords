## Rules for Contributors

* Git commits have to be [signed](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/Contributing/Verify-Git-Commits).
* Keep any discussions civil

## Set up the local environment
#### Requirements
* Internet Connection
* [Node](https://nodejs.org/)
* [Docker](https://store.docker.com/search?type=edition&offering=community)
* [Docker Compose](https://docs.docker.com/compose/install/#install-compose)
* [mkcert](https://github.com/FiloSottile/mkcert)

#### Project Setup
1. Open a shell on your computer and navigate to the projects directory
2. Run `npm install`
3. Run `npm run build`
4. If you have [mkcert](https://github.com/FiloSottile/mkcert), run `npm run mkcert`;
5. Run `npm run start`
6. Open your browser and type [https://localhost/](https://localhost/)
7. Log in with the username `admin` and the password `admin`
8. You can now access the [app](http://localhost/index.php/apps/passwords)

#### Sample Data
The docker setup comes with two test users `max` and `erika` with the password `PasswordsApp`.
All users have a set of sample data installed.
You can also download a [file with sample data](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/SamplePasswords.json) from the wiki and can import it on the [backups page](http://localhost/index.php/apps/passwords#/backup).

#### Helpful commands
* `npm run watch` - Create development build of JS/CSS files and watch for changes
* `npm run start` - Start the docker server. Add :postgres or :ldap to include these services
* `npm run stop` - Stop the docker server. Add :postgres or :ldap to include these services
* `npm run cron` - Execute cron jobs
* `npm run shell` - Open a shell in the container
* `npm run shell:root` - Open a root shell in the container
* `npm run down` - Remove docker containers and volumes
* `npm run docker:build` - Start docker and build images
* `npm run build` - Create production build of JS/CSS files
* `npm run build:nightly` - Create nightly build of JS/CSS files
* `npm run selenium` - Start Selenium server for frontend testing
* `npm run handbook` - Create screenshots for the manual
* `docker-compose -f docker-compose.yml -f docker/configs/<service>.yml up -d` starts the system with additional services
* `docker exec -u www-data passwords-php /var/www/html/occ  config:system:set loglevel --value=0 --type=int` - Set loglevel to info

#### Services
 - [https://localhost](https://localhost) is Nextcloud (with the app)
 - [http://localhost:8080](http://localhost:8080) is PhpMyAdmin (for the MySQL database)
 - [http://localhost:1080](http://localhost:1080) is MailHog (for Emails)
 - [http://localhost:8081](http://localhost:8081) is pgAdmin (for the Postgres database, user `admin@nextcloud`, password `nextcloud`)
 - [https://localhost:9090](https://localhost:9090) is PhpLdapAdmin (optional, user `cn=admin,dc=nextcloud`, password `nextcloud`)

#### LDAP Setup
 - Enable LDAP App
 - Go to "Settings" > "LDAP/AD Integration"
 - Enter `openldap` as server and `389` as port
 - Enter `cn=admin,dc=nextcloud` as User-DN and `nextcloud` as password
 - Enter `dc=nextcloud` as Base DN
 - Click on "Continue"
 - Select "inetOrgPerson" and "posixAccount" as object classes
 - Click "Continue"
 - Check "LDAP-/AP-E-Mail-Address"
 - Select "cn" and "uid" as login attributes

#### First steps
* [Issues for Beginners](https://github.com/marius-wieschollek/passwords/labels/for%3Astarters)
* [Developer Manual](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/Index)