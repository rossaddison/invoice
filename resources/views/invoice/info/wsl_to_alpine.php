<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>
<div style='font-size: 18px;'>
<p><b><?php echo $translator->translate('faq.wsl.to.alpine'); ?></b></p>
<pre><p><b>WSL to Alpine running on Apache2:
 Updating with latest changes seen on Github Repo</b>
 <b>1. Right click on windows 11 icon 🪟 … Run … wsl</b>
 <b>2. Connect to Alpine:</b>
         ssh root@ipaddress or ssh root@yourdomain
 <b>3. Enter your Alpine password:</b>
         (copy... right click ... enter)
 <b>4. Get into root directory:</b>
         cd ..
         dir
         cd var/www/invoice
 <b>5. Verify git is installed before using it:</b>
         git -- version
 <b>6. Upgrade git:</b>
         apk update && apk upgrade git
 <b>7. Check for any local changes that you have made on the website before pulling:</b>
         git status
 <b>8. Always stash your changes depending on git status:</b>
         git stash (Restore with git stash pop)
 <b>9. Or necessary to override your changes:</b>
         git checkout -- . (dash dash space fullstop)
 <b>10. Restoring a specific file from the stash:</b>
        git checkout stash@{0} -- .env
 <b>11. Pull from the repository:
         git pull origin main
 <b>12 a. Accessing the Apline mySql/mariaDB database:
         mysql -u root -p</b>
 <b>12 b. Advisable to make sure the phpMyAdmin endpoint is not visible and that
        phpMyAdmin is not used even with ipaddress restrictions and aliasing.</b>
 <b>23. Finding where phpmyadmin is installed:
        find / -name "index.php" -path "*/phpmyadmin/*" 2>/dev/null</b>
 <b>13. Listing Multiple Stashes:
         git stash list</b>
 <b>14. Getting out of the stash list whilst viewing it by pressing q:
         q</b>
 <b>15. See what is in your stash without applying it:
         git stash show -p</b>
 <b>16. Giving ownership to apache user:
         chown -R apache:apache /var/www/invoice/
         chown -R apache:apache /var/www/invoice/resources/rbac/assignments.php
         chown -R apache:apache /var/www/invoice/resources/rbac/items.php
         chmod -R 755 /var/www/invoice/
         chmod -R 775 /var/www/invoice/resources/
         chmod -R 775 /var/www/invoice/runtime/
         chmod -R 775 /var/www/invoice/public/assets/</b>
 <b>17. There is no need to ensure that the resources/rbac/items.php file,
        when logged in as the admin, is rotating between a visible
        'entry.to.base.controller' and an invisible one. These settings are now
        permanent. RBAC Mutation is avoided as a security measure.
 </b>
 <b>18. Install telnet to check that port 465 is open for smtps
        On alpine: apk add busybox-extras
        On ubuntu: sudo apt install telnet -y
        Checking e.g.: telnet smtp.gmail.com 465
        
        If 'Connection closed by foreign host' is seen, this is normal — e.g.
        Gmail's SMTP server closed the plain telnet connection because it
        expects an SSL handshake, not a raw telnet connection.
 </b>
 <b>19. Ensure that all log files are initially deleted: rm runtime/logs/*.log
        Yiisoft will rebuild them automatically.
        Viewing the initial logfile:
        sudo nano /var/www/invoice/runtime/logs/app.log
 </b>
 <b>20. Settings ... General ... Stop Signing Up ... No
        Use guerrillamail.com to receive the test email addresses
        that are used in the signup process, in order for the recipient to confirm
        their details i.e. clicking on the confirmation link ... and therefore
        confirm that an email can be sent through port 465 using the latest
        more secure symfony-mailer.
 </b>
 <b>21. To Output the first 30 lines of your mailer settings:
        grep -A 30 "yiisoft/mailer-symfony"
            /var/www/invoice/config/common/params.php
 </b>
 <b>22. Terminate your locally run wsl if you are using it so it does not
        conflict with wampserver:
        wsl --terminate Ubuntu/Alpine
 </b>
 <b>23. Update ssl.conf on apache2:
        sudo nano /etc/apache2/conf.d/ssl.conf
        
        Test and restart Apache2:
        httpd -t && rc-service apache2 restart
 </b>
</p>
</pre>
</div>