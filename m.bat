@echo off
title Yii3-i Dev Tools
cd /d "%~dp0"
echo.
echo  Yii3-i Dev Tools
echo  URL: http://127.0.0.1:8099
echo  Stop: Ctrl+C
echo.
start "" http://127.0.0.1:8099
php -S 127.0.0.1:8099 m.php
