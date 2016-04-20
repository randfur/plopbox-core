<?php
// PlopBox FileBrowser Index Generator

// Initialize core variables
$func = 1;
require "/plopbox/pbfunc.php";
$interlink = urldecode(strstr( $_SERVER['REQUEST_URI'], "?", true ) ?: $_SERVER['REQUEST_URI']);
$host = ('http://' . $_SERVER['SERVER_NAME']);
$fullurl = $host . $interlink;
$smlink = $pbuttonsep = $paginator = $dcount = $logmsg2 = $logmsg3 = $logmsg4 = $directories = $files = $stoperror = $opresult = "";
$sortval = $pagebuttons = $itemcount = 0;
$pbconf = parse_ini_file("/plopbox/pbconf.ini", true, INI_SCANNER_TYPED);
$droot = $pbconf['required']['droot'];
$logpath = $pbconf['required']['logpath'];
$secret = $pbconf['required']['secret'];
$dbauth = $pbconf['database'];
$timezone = $pbconf['index_options']['timezone'];
$timestring = $pbconf['index_options']['timestring'];
$fileexclude = $pbconf['index_options']['fileexclude'];
$folderexclude = $pbconf['index_options']['folderexclude'];
$mimetypes = explode(',', $pbconf['index_options']['mimetypes']);
$mimedebug = boolinate($pbconf['index_options']['mimedebug']);
$simplemode = boolinate($pbconf['index_options']['simplemode']);
switch ($pbconf['index_options']['sort']) {
  case "SCANDIR_SORT_ASCENDING":
  $sort = SCANDIR_SORT_ASCENDING;
  break;
  case "SCANDIR_SORT_DESCENDING":
  $sort = SCANDIR_SORT_DESCENDING;
  break;
}

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
  header("HTTP/4.01 403 Forbidden");
  exit;
}

// Revert to defaults if pbconf variables are missing/invalid
if (!isset($sort)) {
  $sort = SCANDIR_SORT_DESCENDING;
}
if (!isset($simplemode)) {
  $simplemode = false;
}
if (!isset($timestring)) {
  $timestring = "M j, Y - g:iA";
}
if (!isset($mimedebug)) {
  $mimedebug = false;
}

// Parse ?logout, ?sort, ?simplemode, and ?start URI arguments
$logout = !empty($_GET['logout']);

if (isset($_GET['sort'])) {
  switch ($_GET['sort']) {
    case 0:
    $sort = SCANDIR_SORT_ASCENDING;
    $sortval = 0;
    break;
    case 1:
    $sort = SCANDIR_SORT_DESCENDING;
    $sortval = 1;
    break;
  }
}
if (isset($_GET['simple'])) {
  $simplemode = boolinate($_GET['simple']);
  if ($simplemode == true) {
    $smlink = '&simple=true';
  }
}
if (!empty($_GET['start']) && ctype_digit($_GET['start'])) {
  $fstart = $_GET['start'];
} else {
  $fstart = 0;
}

// Initialize database connection
try {
  $db = new PDO("sqlite:" . $droot . "/plopbox/db/users.db") or die("Database Error!");
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
  $logmsg = ' ' . $e;
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  $db = null;
  $sth = null;
  exit($e);
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
  session_set_cookie_params(1800, '/', $_SERVER['SERVER_NAME'], false, true);
  session_start();
  if (!isset($_SESSION['stoken'])) {
    $_SESSION['stoken'] = false;
  }
  if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = false;
  }
  if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = false;
  }
  if ($_SESSION['stoken'] !== false) {
    if (valstoken(session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, 1800) == false) {
      $_SESSION['stoken'] = false;
    }
  }
}

// Check if logging out
if ($logout === true) {
  if ($_SESSION['stoken'] !== false) {
    $_SESSION['stoken'] = false;
    $logmsg3 .= ': User "' . $_SESSION['user'] . '" logged out.';
    header('Location: ' . $host );
  }
}

// Session & Login Manager
if (session_status() == PHP_SESSION_ACTIVE) {
  if (valstoken(session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, 1800) == true) {
    // Execute file operations
    if (isset($_GET['fileop'])) {
      if (!empty($_POST['ftoken'])) {
        if (valtoken(session_id(), $_POST['ftoken'], $secret, 900) == true) {
          $ctoken = newtoken(session_id(), $secret);
          require "/plopbox/filemanager.php";
          if ($_GET['fileop'] == 1) {
            if (!empty($_FILES["fileToUpload"]["name"])) {
              $opresult = uploadfile($_FILES["fileToUpload"]["name"], $droot, $interlink, $folderexclude);
            } else { $opresult = "Error: No File"; }
          } else if ($_GET['fileop'] == 2) {
            if (!empty($_POST["foldername"])) {
              $opresult = newfolder($_POST["foldername"], $droot, $interlink, $folderexclude);
            } else { $opresult = "Error: No folder name entered."; }
          } else if ($_GET['fileop'] == 3) {
            if (!empty($_POST["filestotrash"])) {
              $opresult = trashfile($_POST["filestotrash"], $droot, $interlink, $folderexclude);
            } else { $opresult = "Error: No File"; }
          }
        } else {
          $logmsg .= " INVALID/EXPIRED FILE OPERATION TOKEN";
          $_SESSION['stoken'] = false;
          @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
          header("HTTP/4.01 403 Forbidden");
          die;
        }
      } else {
        $logmsg .= " NO FILE OPERATION TOKEN (Suspicious!)";
        $_SESSION['stoken'] = false;
        @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
        header("HTTP/4.01 403 Forbidden");
        die;
      }
    }
  }
  if (valstoken(session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, 1800) == true) {
    $ctoken = newtoken(session_id(), $secret);
    require "/plopbox/core.php";
  } else {
    $ctoken = newtoken(session_id(), $secret);
    require "plopbox/login.php";
    if (valstoken(session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, 1800) == true) {
      $ctoken = newtoken(session_id(), $secret);
      require "/plopbox/core.php";
    }
  }
}

// Write to access log
@file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
?>
