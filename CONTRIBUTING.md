## Set up the local environment
#### Requirements
* Docker
* Docker Compose

#### First run
* Open a shell on your computer and navigate to the projects directory
* Run `docker-compose up -d`
* Run `docker exec -it passwords-php bash`
* Run `chmod 775 custom_apps`
* Run `chown root:www-data custom_apps`
* Open your browser and type [http://localhost/](http://localhost/)
* Create your Nextcloud administrator account
* Click "Storage & Database"
* Select "MySQL/MariaDB" as database
* Enter "nextcloud" for username, password and database
* Enter "db" for database (usually filled already with "localhost")
* Finish the installation
* Go to "Settings" > "Apps" > "Disabled Apps" and enable Passwords

#### First steps
* [Issues for beginners](https://github.com/marius-wieschollek/passwords/labels/for%3Astarters)
* [Wiki](https://github.com/marius-wieschollek/passwords/wiki)