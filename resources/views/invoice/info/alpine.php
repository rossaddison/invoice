<?php
declare(strict_types=1);

?>

<p><b>How can I setup this repository onto Linux Alpine, Apache2, mySql/mariadb and php?</b><p>
<p>To set up the rossaddison/invoice repository on Linux Alpine using Apache2, MySQL/MariaDB, and PHP, follow these steps:</p>

<p><b>1. Install Prerequisites</b></p>
<p>Update Alpine's package index and install the required software:</p>
<pre>
sh
apk update
apk add apache2 apache2-utils mysql mysql-client php php-apache2 php-mbstring php-json php-curl php-session php-xml php-zip php-openssl php-pdo php-pdo_mysql composer git
Apache2: Installs the Apache HTTP server.
MySQL/MariaDB: Installs the database server.
PHP: Installs PHP and required extensions for the repository.
</pre>
<p><b>2. Start and Enable Services</b></p>
<p>Start and enable Apache2 and MySQL services:</p>
<pre>
sh
rc-service apache2 start
rc-update add apache2

rc-service mariadb setup
rc-service mariadb start
rc-update add mariadb
</pre>
<p><b>3. Clone the Repository</b></p>
<p>Clone the repository to your desired location:</p>
<pre>
sh
git clone https://github.com/rossaddison/invoice.git
cd invoice
</pre>
<p><b>4. Configure Apache2<b></p>
<p>Create a new virtual host configuration for the repository:</p>
<pre>
sh
vi /etc/apache2/conf.d/invoice.conf
</pre>
<p>Add the following content:</p>
<pre>
apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /path/to/invoice/public

    <Directory /path/to/invoice/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/apache2/invoice-error.log
    CustomLog /var/log/apache2/invoice-access.log combined
</VirtualHost>
Replace /path/to/invoice with the full path to the repository.

Enable mod_rewrite for Apache:

sh
sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/apache2/httpd.conf
Restart Apache:

sh
rc-service apache2 restart
5. Set Up MySQL/MariaDB
Secure the MariaDB installation:

sh
mysql_secure_installation
Log in to the database and create a new database and user:

sh
mysql -u root -p
Run the following SQL commands:

SQL
CREATE DATABASE invoice;
GRANT ALL PRIVILEGES ON invoice.* TO 'invoice_user'@'localhost' IDENTIFIED BY 'password';
FLUSH PRIVILEGES;
EXIT;
Replace invoice_user and password with your desired username and password.
</pre>
<p><b>6. Install PHP Dependencies</b></p>
<p>Use Composer to install the PHP dependencies:</p>
<pre>
sh
composer install
</pre>
<p><b>7. Configure Environment</b></p>
<p>Set up the environment file:</p>
<pre>
sh
cp .env.example .env
vi .env
Update the .env file with your database credentials:

Code
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice
DB_USERNAME=invoice_user
DB_PASSWORD=password
</pre>
<p><b>8. Run Database Migrations</b></p>
<p>Execute migrations to set up the database schema:</p>
<pre>
sh
php artisan migrate --seed
</pre>
<p><b>9. Set Permissions</b></p>
<p>Ensure the storage and bootstrap/cache directories are writable:</p>
<pre>
sh
chown -R apache:apache storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
</pre>
<p><b>10. Access the Application</b></p>
<p>Visit http://localhost in your browser to access the application.</p>
<p>
Optional Steps
If the repository uses JavaScript (e.g., for front-end assets), install Node.js and npm:
</p>
<pre>
sh
apk add nodejs npm
npm install
npm run dev
If SSL is required, consider installing and configuring Let's Encrypt with certbot.

By following these steps, the rossaddison/invoice repository should be set up and running with Linux Alpine, Apache2, MySQL/MariaDB, and PHP. Let me know if you need further assistance!
</pre>


