<?php
include "pbconf.php";
$host = ('http://' . $_SERVER['SERVER_NAME']);
$interlink = ltrim($_SERVER['HTTP_REFERER'], $host);
$interlink = urldecode($interlink);
$target_files = $droot . $interlink . $_POST["selectedfiles"];

foreach ($target_files as $file) {
	$ftarget = ($droot . '/' . $interlink . $file);
  echo ($ftarget . '<br>');
}
?>
