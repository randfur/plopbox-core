<?php
// PlopBox FileBrowser Index Generator

// Initialize core variables
require "/plopbox/pbconf.php";
require "/plopbox/pbfunc.php";
$interlink = urldecode(strstr( $_SERVER['REQUEST_URI'], "?", true ) ?: $_SERVER['REQUEST_URI']);
$host = ('http://' . $_SERVER['SERVER_NAME']);
$fullurl = $host . $interlink;
$smlink = $paginator = $dcount = $logmsg2 = $logmsg3 = $logmsg4 = $directories = $files = $stoperror = $opresult = "";
$sortval = 0;

// Setup timezone
if (!empty($timezone)) {
  date_default_timezone_set($timezone);
} else if (empty($timezone)){
  $logmsg3 .= ' ERROR: $timezone variable not set in pbconf.php! Defaulting to UTC.';
  date_default_timezone_set("UTC");
}

// Format log entry
$logmsg = date("M d Y, G:i:s e", $_SERVER['REQUEST_TIME']) . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' --> ' . $host . $interlink . ' | STATUS: ';

// Stop execution if any vital extensions/variables are unloaded or undefined
if (!extension_loaded('fileinfo')) {
  $logmsg .= ' ERROR: php_fileinfo extension not loaded!';
  $logmsg2 .= ' ERROR: php_fileinfo extension not loaded!<br>';
  $stoperror = true;
}
if (!extension_loaded('pdo_sqlite')) {
  $logmsg .= ' ERROR: php_pdo_sqlite extension not loaded!';
  $logmsg2 .= ' ERROR: php_pdo_sqlite extension not loaded!<br>';
  $stoperror = true;
}
if (!extension_loaded('sqlite3')) {
  $logmsg .= ' ERROR: php_sqlite3 extension not loaded!';
  $logmsg2 .= ' ERROR: php_sqlite3 extension not loaded!<br>';
  $stoperror = true;
}
if (empty($secret)) {
  $logmsg .= ' ERROR: $secret variable not set in pbconf.php!';
  $logmsg2 .= ' ERROR: $secret variable not set in pbconf.php!<br>';
  $stoperror = true;
}
if (empty($droot)) {
  $logmsg .= 'ERROR: $droot variable not set in pbconf.php!';
  $logmsg2 .= 'ERROR: $droot variable not set in pbconf.php!<br>';
  $stoperror = true;
}
if (session_status() == PHP_SESSION_DISABLED) {
  $logmsg .= ' ERROR: PHP sessions are disabled!';
  $logmsg2 .= ' ERROR: PHP sessions are disabled!<br>';
  $stoperror = true;
}
if (empty($logpath)) {
  $logmsg .= ' ERROR: $logpath variable not set in pbconf.php!';
  $logmsg2 .= ' ERROR: $logpath variable not set in pbconf.php!<br>';
  syslog(LOG_ERR, 'PlopBox:' . $logmsg);
  exit($logmsg2);
}
if ($stoperror == true) {
  @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
  exit($logmsg2);
}

// Stop execution if the URI contains an excluded directory
if (preg_match($folderexclude, $interlink) === 1) {
  $logmsg .= ' ACCESS DENIED';
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  header("HTTP/1.0 403 Forbidden");
  exit;
}

// Parse ?logout, ?sort, ?simplemode, and ?start URI arguments
if (!empty($_GET['logout'])) {
  if ($_GET['logout'] == true) {
    $logout = true;
  } else {
    $logout = false;
  }
} else {
  $logout = false;
}
if (isset($_GET['sort'])) {
  if ($_GET['sort'] == 1) {
    $sort = SCANDIR_SORT_DESCENDING;
    $sortval = 1;
  }
}
if (isset($_GET['simple'])) {
  if ($_GET['simple'] == 1) {
    $simplemode = 1;
  } else {
    $simplemode = 0;
    $smlink = '&simple=0';
  }
}
if (!empty($_GET['start'])) {
  $fstart = $_GET['start'];
} else {
  $fstart = 0;
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
  session_start();
  if (!isset($_SESSION['stoken'])) {
    $_SESSION['stoken'] = false;
  }
  if ($_SESSION['stoken'] !== false) {
    if (valtoken($_SESSION['stoken'], $secret, 1800) == false) {
      $_SESSION['stoken'] = false;
    }
  }
}

// Check if logging out
if ($logout == true) {
  if ($_SESSION['stoken'] !== false) {
    $_SESSION['stoken'] = false;
    $logmsg3 .= ': User "' . $_SESSION['user'] . '" logged out.';
    header('Location: ' . $host );
  }
}

// Session & Login Manager
if (session_status() == PHP_SESSION_ACTIVE) {
  if (valtoken($_SESSION['stoken'], $secret, 1800) == true) {
    // Execute file operations
    if (isset($_GET['fileop'])) {
      if (!empty($_POST['ftoken'])) {
        if (valtoken($_POST['ftoken'], $secret, 900) == true) {
          $ctoken = newtoken($secret);
          require "/plopbox/filemanager.php";
          if ($_GET['fileop'] == 1) {
            if (!empty($_FILES["fileToUpload"]["name"])) {
              $opresult = uploadfile($_FILES["fileToUpload"]["name"], $droot, $interlink, $folderexclude);
            } else { $opresult = "No File"; }
          } else if ($_GET['fileop'] == 2) {
            if (!empty($_POST["foldername"])) {
              $opresult = newfolder($_POST["foldername"], $droot, $interlink, $folderexclude);
            } else { $opresult = "No folder name entered."; }
          } else if ($_GET['fileop'] == 3) {
            if (!empty($_POST["filestotrash"])) {
              $opresult = trashfile($_POST["filestotrash"], $droot, $interlink, $folderexclude);
            } else { $opresult = "No File"; }
          }
        } else {
          $logmsg .= " INVALID/EXPIRED FILE OPERATION TOKEN";
          $_SESSION['stoken'] = false;
          @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
          header("HTTP/1.0 403 Forbidden");
          die;
        }
      } else {
        $logmsg .= " NO FILE OPERATION TOKEN (Suspicious!)";
        $_SESSION['stoken'] = false;
        @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
        header("HTTP/1.0 403 Forbidden");
        die;
      }
    }
  }
  if (valtoken($_SESSION['stoken'], $secret, 1800) == true) {
    $ctoken = newtoken($secret);
    require "/plopbox/core.php";
  } else {
    $ctoken = newtoken($secret);
    require "plopbox/login.php";
    if (valtoken($_SESSION['stoken'], $secret, 1800) == true) {
      $ctoken = newtoken($secret);
      require "/plopbox/core.php";
    }
  }
}

// Write to access log
@file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
?>
