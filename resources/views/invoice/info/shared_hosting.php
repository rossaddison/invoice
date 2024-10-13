<?php 
declare(strict_types=1);

?>

<p><b>How do I host yii3i on shared hosting?</b></p>


Insert the following code at your root. So if all the files relating to your site are located in a folder yii3i, the .htaccess file should be located in the root that holds this yii3i folder ie. at the top of the tree.</p>

<p><b>Purpose</b><br>
.htaccess file located at root (yii3i.co.uk/) rebasing to /yii3i (main folder yii3-i-main) directing to public folder</p>

<a href="https://stackoverflow.com/questions/23635746/htaccess-redirect-from-site-root-to-public-folder-hiding-public-in-url/23638209#23638209">Stackoverflow</a>
<br>
<br>
<p><b>Code</b><br>
RewriteEngine On<br>
RewriteBase /yii3i<br>
RewriteCond %{THE_REQUEST} /public/([^\s?]*) [NC]<br>
RewriteRule ^ %1 [L,NE,R=302]<br>
RewriteRule ^((?!public/).*)$ public/$1 [L,NC]<br></p>
<br>
<p><b>No access to composer update</b><br>
More than often on shared hosting you will not be able to run a composer update. 
   Your focus will be on replacing your vendor folder through SFTP/FTP e.g. filezilla, and your /config/.merge-plan.php file from your localhost setup.</p>
<p><b>One.com Shared Hosting for testing rossaddison/invoice yii3i.co.uk</b></p>
<p>config/common/params.php</p>
<p><pre>
'mailer' => [
    'adminEmail' => 'admin@example.com',
    /**
     * Note: This setting is critical to the sending of emails since it is used in SettingsRepository getConfigSenderEmail()
     * Used in critical function e.g src/Auth/Controller/SignUpController function signup amd 
     * src/Auth/Controller/ForgotController function forgot 
     */  
    'senderEmail' => 'sender@your.web.site.domain.com'
],
'symfony/mailer' => [
    'esmtpTransport' => [
       'enabled' => true, 
       'scheme' => 'smtp', // "smtps": using TLS, "smtp": without using TLS.
       'host' => 'send.one.com',
       'port' => 465,
       'username' => --------------> same as senderEmail i.e sender@your.web.site.domain.com,
       'password' => 'YourPasswordHere',
       'options' => [], // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification
    ],
],
'yiisoft/yii-cycle => [
    'connections' => [
        'mysql' => new \Cycle\Database\Config\MySQLDriverConfig(
            connection:
            new \Cycle\Database\Config\MySQL\DsnConnectionConfig('mysql:host=localhost;dbname=domain_com',
              'user_domain_com',   -------------> localhost 'root'
              'password_domain_com'),    ----------------> localhost ''
            driver: \Cycle\Database\Driver\MySQL\MySQLDriver::class,
        ),
    ],
]    
</pre></p>
