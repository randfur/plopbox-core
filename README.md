# PlopBox File Manager/Browser
Plopbox-Core Installation Guide v1.1


###### Pre-Requisites

**PlopBox _requires_ the following software to be installed & correctly configured:**
- PHP 5.6+
- "sqlite3" PHP Extension
- "pdo_sqlite" PHP Extension
- "fileinfo" PHP Extension
- Apache HTTP Server, or compatible equivalent.
- Apache's "mod_mime" module, or compatible equivalent.


###### Installation Instructions

 1. Unzip the contents of "master.zip" into the top-level directory of your document root.
(Or the highest level directory you would like PlopBox to be able to browse)
(In Apache by default this is C:/Apache/htdocs)

 2. Verify the "php_pdo_sqlite", "php_sqlite3", and "php_filefinfo" extensions are loaded (uncommented) in your "php.ini" file.

 3. Specify the absolute filesystem path of "index.php" as the Directory Index in your HTTP server.
(In Apache by default this is C:/Apache/htdocs/index.php)

 4. Edit "/plopbox/pbconf.php" and configure the variables accordingly, following the instructions in the file.
(Configuration of this file is *required*. PlopBox will not function otherwise.)
