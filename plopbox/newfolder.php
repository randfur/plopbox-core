<?php
include "pbconf.php";
$host = ('http://' . $_SERVER['SERVER_NAME']);
$interlink = ltrim($_SERVER['HTTP_REFERER'], $host);
$interlink = urldecode($interlink);
$folder_dest = $droot . $interlink . $_POST["foldername"];
$uploadOk = 1;

if (ctype_alnum($_POST["foldername"])) {
	if (file_exists($folder_dest)) {
    echo "Folder or file already exists. ";
    $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Your folder was not created. ";
// if everything is ok, try to make folder
} else {
    if (mkdir($folder_dest)) {
        echo "The folder ". basename($folder_dest). " has been created.";
    } else {
        echo "There was an error creating the folder.";
    }
}
} else {
	echo "Only letters A-Z and numbers will be accepted. Your folder was not created.";
}
?>
