PlopBox File Manager v0.1
Plopbox-Core Installation Guide v1.0
Last Edited 2016-03-17T21:51:11+00:00
-

*Installation Instructions*

 1. Unzip the contents of "master.zip" into the top-level directory of your document root.
(Or the highest level directory you would like PlopBox to be able to browse)
(In Apache by default this is C:/Apache/htdocs)

 2. Specify the absolute path of "index.php" as the Directory Index in your HTTP server.
(In Apache by default this is C:/Apache/htdocs/index.php)

 3. Edit "pbconf.php" and configure the variables accordingly, following the instructions in the file.
(Only the "$droot" and "$timezone" variables must be set in order for PlopBox to function)
