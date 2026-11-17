HEZA SYSTEM on XAMPP
====================
1. Start Apache and MySQL in the XAMPP Control Panel.
2. Copy this whole folder ("heza") into C:\xampp\htdocs\
   so you have C:\xampp\htdocs\heza\index.html and api.php
3. Open http://localhost/heza/ in your browser.

The database "heza_system" and its table "records" are created
automatically the first time the page loads. View them in
http://localhost/phpmyadmin

If your MySQL root user has a password, edit $cfg at the top of api.php.
Open the page through http://localhost/..., not by double-clicking
index.html (PHP will not run from a file:// address).
