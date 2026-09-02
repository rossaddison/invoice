<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>
<div style="font-size: var(--inv-form-fs);">

<p><b><?php echo $translator->translate('faq.wsl.to.alpine'); ?></b></p>

<div class="alert alert-info" role="alert">
 <b>Don't want to read all of this?</b> Once you're connected (section 1
 below), run:
 <pre style="margin: 0.5rem 0 0;">cd /var/www/invoice
git pull origin main
sh bin/alpine-menu.sh</pre>
 It's an interactive numbered menu covering most of sections 3&ndash;13
 below &mdash; git pull, verifying <code>.env</code>/RBAC, permissions,
 restarting Apache/MariaDB, clearing the route cache, backing up the
 database, updating Node. Type the number of whatever you need; you don't
 have to know this page's order to use it.
</div>

<p><b>On this page:</b></p>
<ol>
 <li><a href="#connect">Connect from Windows to Alpine</a></li>
 <li><a href="#app-root">Get to the app root</a></li>
 <li><a href="#git-workflow">Update the code (git)</a></li>
 <li><a href="#verify-after-pull">Verify after pulling</a></li>
 <li><a href="#database">Database access</a></li>
 <li><a href="#permissions">File ownership &amp; permissions</a></li>
 <li><a href="#rbac-note">RBAC note</a></li>
 <li><a href="#mail-smtp">Mail / SMTP testing</a></li>
 <li><a href="#logs">Logs</a></li>
 <li><a href="#apache-ssl">Apache / SSL</a></li>
 <li><a href="#route-cache">Route cache</a></li>
 <li><a href="#backup">Database backup</a></li>
 <li><a href="#node">Updating Node.js</a></li>
</ol>

<hr>

<h5 id="connect">1. Connect from Windows to Alpine</h5>
<ol>
 <li>Right-click the Windows 11 icon 🪟 &hellip; Run &hellip; <code>wsl</code></li>
 <li>Connect to Alpine:
  <pre>ssh root@ipaddress
# or
ssh root@yourdomain</pre>
 </li>
 <li>Enter your Alpine password (copy it, then right-click to paste at the prompt).</li>
</ol>
<p>When you're done and you were also running WSL locally, terminate it so it
 doesn't conflict with wampserver: <code>wsl --terminate Ubuntu/Alpine</code></p>

<h5 id="app-root">2. Get to the app root</h5>
<pre>cd /var/www/invoice</pre>

<h5 id="git-workflow">3. Update the code (git)</h5>
<ol>
 <li>Verify git is installed: <code>git --version</code></li>
 <li>Upgrade git: <code>apk update &amp;&amp; apk upgrade git</code></li>
 <li>Check for local changes before pulling: <code>git status</code></li>
 <li>Stash any local changes (restore later with <code>git stash pop</code>): <code>git stash</code></li>
 <li>Or, to discard them entirely instead: <code>git checkout -- .</code></li>
 <li>Restoring one specific file from the stash, e.g. <code>.env</code>: <code>git checkout stash@{0} -- .env</code></li>
 <li>Pull: <code>git pull origin main</code></li>
 <li>List multiple stashes: <code>git stash list</code></li>
 <li>See what's in a stash without applying it: <code>git stash show -p</code>
  (press <code>q</code> to exit the pager)</li>
</ol>

<h5 id="verify-after-pull">4. Verify after pulling</h5>
<ul>
 <li>Confirm <code>.env</code> exists at the root &mdash; otherwise you'll likely get a
  500 error. If it's missing, restore it from the stash (section 3, step 6
  above).</li>
 <li>Confirm the static/unchanging <code>resources/rbac/items.php</code> exists.</li>
 <li>Verify role assignments:
  <pre>mysql -u root -p yii3_i -e "SELECT * FROM yii_rbac_assignment;"</pre>
 </li>
 <li>If no rows come back, assign the two default roles:
  <pre>php yii user/assignRole admin 1
php yii user/assignRole observer 2</pre>
 </li>
 <li>After a pull, clear the Yii3 route cache so new routes are picked up
  immediately &mdash; see <a href="#route-cache">Route cache</a> below.</li>
</ul>

<h5 id="database">5. Database access</h5>
<ul>
 <li>Open an interactive MySQL/MariaDB shell: <code>mysql -u root -p</code></li>
 <li>Make sure the phpMyAdmin endpoint is not publicly visible, and don't
  use phpMyAdmin even with IP restrictions/aliasing. Find where (or
  whether) it's installed:
  <pre>find / -name "index.php" -path "*/phpmyadmin/*" 2>/dev/null</pre>
 </li>
 <li>Check MariaDB is running (useful when you see connection errors):
  <code>rc-service mariadb status</code></li>
 <li>Restart MariaDB: <code>rc-service mariadb restart</code></li>
</ul>

<h5 id="permissions">6. File ownership &amp; permissions</h5>
<pre>chown -R apache:apache /var/www/invoice/
chown -R apache:apache /var/www/invoice/resources/rbac/assignments.php
chown -R apache:apache /var/www/invoice/resources/rbac/items.php
chmod -R 755 /var/www/invoice/
chmod -R 775 /var/www/invoice/resources/
chmod -R 775 /var/www/invoice/runtime/
chmod -R 775 /var/www/invoice/public/assets/</pre>

<h5 id="rbac-note">7. RBAC note</h5>
<p>There is no need to worry if <code>resources/rbac/items.php</code>, when
 logged in as admin, appears to rotate between a visible
 <code>entry.to.base.controller</code> and an invisible one. These
 settings are permanent &mdash; RBAC mutation is avoided as a security
 measure.</p>

<h5 id="mail-smtp">8. Mail / SMTP testing</h5>
<ul>
 <li>Install telnet to check that port 465 is open for SMTPS:
  <pre># On Alpine:
apk add busybox-extras
# On Ubuntu:
sudo apt install telnet -y

# Then check, e.g.:
telnet smtp.gmail.com 465</pre>
  Seeing "Connection closed by foreign host" is <b>normal</b> here &mdash;
  e.g. Gmail's SMTP server closes a plain telnet connection because it
  expects an SSL handshake, not a raw telnet connection.
 </li>
 <li>To test the whole signup &rarr; confirmation-email path end to end
  without a real inbox: Settings &hellip; General &hellip; Stop Signing Up
  &hellip; No, then use <a href="https://www.guerrillamail.com" target="_blank" rel="noopener">guerrillamail.com</a>
  to receive the test address's confirmation email and click the link
  &mdash; confirming mail sends through port 465 via the current
  symfony-mailer setup.</li>
 <li>Output the first 30 lines of your mailer settings:
  <pre>grep -A 30 "yiisoft/mailer-symfony" /var/www/invoice/config/common/params.php</pre>
 </li>
</ul>

<h5 id="logs">9. Logs</h5>
<p>Make sure all log files are initially deleted &mdash; Yii rebuilds them automatically:</p>
<pre>rm runtime/logs/*.log</pre>
<p>Viewing the live log file: <code>sudo nano /var/www/invoice/runtime/logs/app.log</code></p>

<h5 id="apache-ssl">10. Apache / SSL</h5>
<p>Edit <code>ssl.conf</code> if needed: <code>sudo nano /etc/apache2/conf.d/ssl.conf</code></p>
<p>Then test the config and restart Apache:</p>
<pre>httpd -t &amp;&amp; rc-service apache2 restart</pre>

<h5 id="route-cache">11. Route cache</h5>
<p>Yii3 compiles <code>routes.php</code> into a cache file in
 <code>runtime/cache/</code> on first boot. A <code>git pull</code> updates
 the PHP files on disk, but the stale cache keeps being served until it's
 cleared &mdash; new routes 404 until this is done:</p>
<pre>rm -rf /var/www/invoice/runtime/cache/*</pre>
<p>Yii3 rebuilds the cache automatically on the next request.</p>

<h5 id="backup">12. Database backup</h5>
<p>From the server, at the root:</p>
<pre>mysqldump -u root -p --single-transaction yii3_i | gzip > /tmp/invoice_backup_$(date +%Y%m%d_%H%M%S).sql.gz</pre>
<p>(<code>-p</code> with no value prompts for the password interactively &mdash; never put it inline on the command line, it ends up in shell history.)</p>
<p>Then, from your local machine, to copy it down:</p>
<pre>scp root@yii3i.online:/tmp/invoice_backup_*.sql.gz /mnt/c/wamp64/www/invoice/backups/</pre>
<p>(You'll be prompted for the server password.)</p>

<h5 id="node">13. Updating Node.js</h5>
<p><code>package.json</code>'s own <code>"engines"</code> constraint is
 <code>node ^22.22.3 || ^24.15.0 || >=26.0.0</code>. This box is Alpine
 (musl libc), not glibc &mdash; nvm's precompiled Node builds generally
 don't work here, and nvm isn't installed (confirmed:
 <code>"nvm: not found"</code>). Node is managed via <code>apk</code> instead:</p>
<pre>apk update
apk info nodejs      # see what's currently installed/available
apk upgrade nodejs npm
node -v</pre>
<p>For a specific version not offered by the currently enabled repos,
 check what's available before guessing:</p>
<pre>apk search nodejs</pre>
<p>Apache's service account reads its own environment &mdash; if anything
 shells out to <code>node</code> from a web request (e.g. the Playwright
 PDF button) can't find it after an update, point <code>NODE_BINARY</code>
 in <code>.env</code> at its absolute path (<code>which node</code>)
 rather than relying on <code>PATH</code>.</p>

</div>
