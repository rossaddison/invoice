<?php 
declare(strict_types=1); 
?>

<p><b>How do I host yii3i on shared hosting?</b></p>

<p><b>Method</b><br>
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

