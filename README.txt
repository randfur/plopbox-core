PlopBox File Manager/Browser
Plopbox-Core Installation Guide v1.2


------------------------------------------
Pre-Requisites:
------------------------------------------
PlopBox requires the following software to be installed & correctly configured:
- PHP 5.6+
- "mysql" PHP Extension
- "pdo_mysql" PHP Extension
- "fileinfo" PHP Extension
- A working MIME/magic server module.


------------------------------------------
Installation Instructions:
------------------------------------------
 1. Unzip the contents of "master.zip" into any folder within your document-root.

 2. Verify the "php_pdo_sqlite", "php_sqlite3", and "php_filefinfo" extensions are loaded (uncommented) in your "php.ini" file.

 3. Verify your web server allows the 2 included .htaccess files to function.
 (PlopBox will not function otherwise.)

 4. Edit "/plopbox/pbconf.ini" and configure the variables accordingly, following the instructions in the file.
(Configuration of this file is required. PlopBox will not function otherwise.)
