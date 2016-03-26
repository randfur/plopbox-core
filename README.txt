PlopBox File Manager/Browser
Plopbox-Core Installation Guide v1.1
-

Pre-Requisites:
------------------------------------------
PlopBox requires the following software to be installed & correctly configured:
- SQLite 3
- Apache HTTP Server, or compatible equivalent.
- Apache's "mod_mime.so" module, or compatible equivalent.

-

Installation Instructions:
------------------------------------------
 1. Unzip the contents of "master.zip" into the top-level directory of your document root.
(Or the highest level directory you would like PlopBox to be able to browse)
(In Apache by default this is C:/Apache/htdocs)

 2. Specify the absolute filesystem path of "index.php" as the Directory Index in your HTTP server.
(In Apache by default this is C:/Apache/htdocs/index.php)

 3. Edit "/plopbox/pbconf.php" and configure the variables accordingly, following the instructions in the file.
(Configuration of this file is required. PlopBox will not function otherwise.)
