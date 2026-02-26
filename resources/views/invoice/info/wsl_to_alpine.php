<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>

<p><b><?php echo $translator->translate('faq.wsl.to.alpine'); ?></b></p>
<pre><p><b>WSL to Alpine: Updating with latest changes seen on Github Repo</b>
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
 <b>8. Maybe necessary to stash your changes depending on git status:</b>
         git stash (Restore with git stash pop)
 <b>9. Or necessary to override your changes:</b>
         git checkout -- . (dash dash space fullstop)
 <b>10. Restoring a specific file from the stash:</b>
         git checkout stash@{0} -- config/common/params.php
 <b>11. Pull from the repository:
         git pull origin main
 <b>12. Accessing the Apline mySql/mariaDB database:
         mysql -u root -p</b>
 <b>13. Listing Multiple Stashes:
         git stash list</b>
 <b>14. Getting out of the stash list whilst viewing it by pressing q:
         q</b>
 <b>15. See what is in your stash without applying it:
         git stash show -p</b>
 <b>16. Giving ownership to apache user:
         chown -R apache:apache /var/www/invoice/
         chmod -R 755 /var/www/invoice/
         chmod -R 775 /var/www/invoice/resources/
         chmod -R 775 /var/www/invoice/runtime/
         chmod -R 775 /var/www/invoice/public/assets/</b>
 <b>17. Ensure that the resources/rbac/items.php file, when logged in
        as the admin, is rotating between a visible 'entry.to.base.controller'
        and invisible.
 </b>
</p>
</pre>
