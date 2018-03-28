## Set up the local environment
#### Requirements
* [Node](https://nodejs.org/)
* [Docker](https://store.docker.com/search?type=edition&offering=community)
* [Docker Compose](https://docs.docker.com/compose/install/#install-compose)

#### Project Setup
* Open a shell on your computer and navigate to the projects directory
* Run `npm run start`
* Open your browser and type [http://localhost/](http://localhost/)
* Allow the connection using a self signed https certificate
* Log in with the username `admin` and the password `admin`
* Run `docker exec -u www-data passwords-php /var/www/html/occ app:enable passwords`
* Run `npm install`
* Run `npm run build`
* You can now access the [app](http://localhost/index.php/apps/passwords)

#### Sample Data
A [file with sample data](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/Sample%20Passwords.json) is available in the wiki and can be imported on the [backups page](http://localhost/index.php/apps/passwords#/backup).

#### Helpful commands
* `npm run start` - Start the docker server
* `npm run stop` - Stop the docker server
* `npm run watch` - Create development build of JS/CSS files and watch for changes
* `npm run build` - Create production build of JS/CSS files
* `npm run build:nightly` - Create nightly build of JS/CSS files
* `docker-compose -f docker-compose.yml -f docker/configs/<service>.yml up -d` starts the system with additional services
* `docker exec -u www-data passwords-php php /var/www/html/cron.php` - Execute cron jobs
* `docker exec -u www-data passwords-php /var/www/html/occ  config:system:set loglevel --value=0 --type=int` - Set loglevel to info
* `docker exec -itu www-data passwords-php bash` - Open shell in container as www-data
* `docker exec -it passwords-php bash` - Open shell in container as root

#### Services
 - [https://localhost](https://localhost) is Nextcloud (with the app)
 - [http://localhost:8080](http://localhost:8080) is PhpMyAdmin (for the MySQL database)
 - [http://localhost:1080](http://localhost:1080) is MailHog (for Emails)
 - [http://localhost:7070](http://localhost:7070) is Besticon Iconserver (optional)
 - [https://localhost:9090](https://localhost:9090) is PhpLdapAdmin (optional)

#### First steps
* [Issues for Beginners](https://github.com/marius-wieschollek/passwords/labels/for%3Astarters)
* [Developer Manual](https://git.mdns.eu/nextcloud/passwords/wikis/Developers/Index)