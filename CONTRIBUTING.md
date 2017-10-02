## Set up the local environment
#### Requirements
* NPM
* Docker
* Docker Compose

#### First run
* Open a shell on your computer and navigate to the projects directory
* Run `docker-compose up -d`
* Run `docker exec passwords-php chown www-data:www-data /var/www/html/custom_apps`
* Open your browser and type [http://localhost/](http://localhost/)
* Create your Nextcloud administrator account
* Click "Storage & Database"
* Select "MySQL/MariaDB" as database
* Enter "nextcloud" for username, password and database
* Enter "db" for database (usually filled already with "localhost")
* Finish the installation
* Run `docker exec -u www-data passwords-php /var/www/html/occ app:enable passwords`
* Run `npm install`
* Run `npm run build`
* You can now access the [app](http://localhost/index.php/apps/passwords)

#### Helpful commands
* `npm run build` - Build JS/CSS from vue files
* `npm run watch` - Build JS/CSS from vue files and watch for changes

#### First steps
* [Issues for beginners](https://github.com/marius-wieschollek/passwords/labels/for%3Astarters)
* [Wiki](https://github.com/marius-wieschollek/passwords/wiki)