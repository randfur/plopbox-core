<?php
// PlopBox FileBrowser Index Generator

// Initialize core variables
require "/plopbox/pbconf.php";
require "/plopbox/pbfunc.php";
$interlink = urldecode(strstr( $_SERVER['REQUEST_URI'], "?", true ) ?: $_SERVER['REQUEST_URI']);
$host = ('http://' . $_SERVER['SERVER_NAME']);
$logmsg2 = $logmsg3 = $directories = $files = $stoperror = "";

// Setup timezone
if (empty($timezone) == 0) {
  date_default_timezone_set($timezone);
} else if (empty($timezone) == 1){
  $logmsg3 .= ' ERROR: $timezone variable not set in pbconf.php! Defaulting to UTC.';
  date_default_timezone_set("UTC");
}

// Format log entry
$logmsg = date("M d Y, G:i:s e", $_SERVER['REQUEST_TIME']) . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' > ' . $host . $interlink . ' | STATUS: ';

// Log write error
function logerror() {
  echo 'ERROR writing PlopBox log!';
  $logmsg2 = 'ERROR writing PlopBox log!';
  syslog(LOG_ERR, 'PlopBox: ERROR writing PlopBox log!');
}

// Stop execution if any vital extensions or variables are unloaded or undefined
if (!extension_loaded('fileinfo')) {
  $logmsg .= " ERROR: php_fileinfo extension not loaded!";
  $logmsg2 .= " ERROR: php_fileinfo extension not loaded!<br>";
  $stoperror = true;
}
if (!extension_loaded('pdo_sqlite')) {
  $logmsg .= " ERROR: php_pdo_sqlite extension not loaded!";
  $logmsg2 .= " ERROR: php_pdo_sqlite extension not loaded!<br>";
  $stoperror = true;
}
if (!extension_loaded('sqlite3')) {
  $logmsg .= " ERROR: php_sqlite3 extension not loaded!";
  $logmsg2 .= " ERROR: php_sqlite3 extension not loaded!<br>";
  $stoperror = true;
}
if (empty($secret)) {
  $logmsg .= ' ERROR: $secret variable not set in pbconf.php!';
  $logmsg2 .= ' ERROR: $secret variable not set in pbconf.php!<br>';
  $stoperror = true;
}
if (empty($droot)) {
  $logmsg .= ' ERROR: $droot variable not set in pbconf.php!';
  $logmsg2 .= ' ERROR: $droot variable not set in pbconf.php!<br>';
  $stoperror = true;
}
if (empty($sessions)) {
  $logmsg .= ' ERROR: $sessions variable not set in pbconf.php!';
  $logmsg2 .= ' ERROR: $sessions variable not set in pbconf.php!<br>';
  $stoperror = true;
}
if (empty($logpath)) {
  $logmsg2 .= ' ERROR: $logpath variable not set in pbconf.php!<br>';
  syslog(LOG_ERR, 'PlopBox:' . $logmsg2);
  exit($logmsg2);
}
if ($stoperror == true) {
  @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
  exit($logmsg2);
}

// Stop execution if specified directory is an excluded directory
if (preg_match($folderexclude, $interlink) === 1) {
  $logmsg .= ' ACCESS DENIED';
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  header("HTTP/1.0 403 Forbidden");
  exit;
}

// Parse ?sort URI argument
if (isset($_GET['sort'])) {
  if ($_GET['sort'] == 1) { $sort = str_replace('1', SCANDIR_SORT_DESCENDING, $_GET['sort']); }
  $sortval = $_GET['sort'];
} else {
  $sortval = 0;
}
// Parse ?simplemode URI argument
if (isset($_GET['simple'])) {
  if ($_GET['simple'] == 1) {
    $simplemode = 1;
  } else {
    $simplemode = 0;
    $sslink = '&simple=0';
  }
}

// Session & Login Manager
session_save_path($sessions);
if (session_status() == PHP_SESSION_DISABLED) {
  $logmsg1 .= " ERROR: PHP sessions are disabled. Enable PHP sessions to continue.";
  $logmsg2 .= " ERROR: PHP sessions are disabled. Enable PHP sessions to continue.";
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  exit($logmsg2);
} else if (session_status() == PHP_SESSION_NONE) {
  session_start();
  $_SESSION['valid'] = false;
  $_SESSION['timeout'] = $_SERVER['REQUEST_TIME'];
}
if (session_status() == PHP_SESSION_ACTIVE) {
  // Check session timeout
  if ($_SESSION['timeout'] - $_SERVER['REQUEST_TIME'] > 900) {
    $_SESSION['valid'] = false;
  }
} if ($_SESSION['valid'] == false) {
  $ctoken = newtoken($secret);
  require "plopbox/login.php";
}
if ($_SESSION['valid'] == true) {
  // Check and execute file operations
  if (isset($_GET['fileop'])) {
    if (!empty($_POST['ftoken'])) {
      if (valtoken($_POST['ftoken'], 900) == true) {
        $ctoken = newtoken($secret);
        require "/plopbox/filemanager.php";
        if ($_GET['fileop'] == 1) {
          if (!empty($_FILES["filetoupload"]["name"])) {
            $opresult = uploadfile($_FILES["fileToUpload"]["name"]);
          }
        } else if ($_GET['fileop'] == 2) {
          if (!empty($_POST["foldername"])) {
            $opresult = newfolder($_POST["foldername"]);
          }
        } else if ($_GET['fileop'] == 3) {
          if (!empty($_POST["filestotrash"])) {
            if (trashfile($_POST["filestotrash"]) == 0) {

            }
          }
        }
      } else {
        $logmsg .= " INVALID/EXPIRED TOKEN";
        $_SESSION['valid'] = false;
        @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
        die("ACCESS DENIED");
      }
    } else {
      $logmsg .= " NO TOKEN (Suspicious!)";
      $_SESSION['valid'] = false;
      @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
      die("ACCESS DENIED");
    }
  }
  if ($_SESSION['valid'] == true) {
    $ctoken = newtoken($secret);
    require "/plopbox/core.php";
  } else if ($_SESSION['valid'] == false) {
    $ctoken = newtoken($secret);
    require "/plopbox/login.php";
  }
  if ($_SESSION['valid'] == false) {
    $ctoken = newtoken($secret);
    require "/plopbox/login.php";
  }
}

// Write to access log
@file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
?>
