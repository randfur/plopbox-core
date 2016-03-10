PlopBox File Manager v0.1
Plopbox-Core Installation Guide v1.0
Last Edited: 122016HE-03-10T00:17:06+00:00
-

*Installation Instructions*

 1. Unzip the contents of "plopbox-core.zip" into the top-level directory of your document root.
(Or the highest level directory you would like PlopBox to be able to browse)
(In Apache by default this is /htdocs)

 2. Specify the relative path of "index.php" as the Directory Index in your HTTP server.
(In Apache by default this is /htdocs/index.php)

 3. Edit "pconf.php" and configure the variables accordingly, following the instructions in the file.
(Only the "$droot" and "$timezone" variables must be set in order for PlopBox to function)