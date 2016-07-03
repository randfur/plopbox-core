<?php
// PlopBox FileBrowser Controller Core

// Initialize core variables
$funcload = 1;
require "plopbox/php/pbfunc.php";
$interlink = urldecode(strstr( $_SERVER['REQUEST_URI'], "pbindex.php/?", true ) ?: $_SERVER['REQUEST_URI']);
$interlink = urldecode(strstr( $_SERVER['REQUEST_URI'], "pbindex.php/", true ) ?: $_SERVER['REQUEST_URI']);
$host = ('http://' . $_SERVER['SERVER_NAME']);
$fullurl = $host . rtrim($interlink, '/');
$output = $smlink = $pbuttonsep = $paginator = $dcount = $logmsg2 = $logmsg3 = $logmsg4 = $directories = $stoperror = $opresult = $sortnamearrow = $sortnameval = $sortdatelink = $sortdatearrow = $sortsizearrow = $sortsizelink = $sortsizearrow = "";
$files = $directories = $ddcont = $fdcont = $perm = array();
$idsort = $sortdateval = 2;
$sortsizeval = 4;
$sortval = $pagebuttons = $itemcount = $key = $dsort = $fkey = $dkey = 0;

// Format log entry
$logmsg = date("M d Y, G:i:s e", $_SERVER['REQUEST_TIME']) . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' --> ' . $host . $interlink . ' | STATUS: ';

// Check existence of config file
if (!file_exists("plopbox/pbconf.ini")) {
  $logmsg .= $logmsg3 = ' ERROR: Configuration file "pbconf.ini" is missing! Did you forget to rename "default-pbconf.ini"?';
  syslog(LOG_ERR, 'PlopBox:' . $logmsg);
  echo json_encode(array("opcode" => "LoginPage", "statcode" => "Error", "failmsg" => 'ERROR: Configuration file "pbconf.ini" is missing! Did you forget to rename "default-pbconf.ini"?'));
  exit();
}

// Scan variables from pbconf.ini
$pbconf = parse_ini_file("plopbox/pbconf.ini", true, INI_SCANNER_NORMAL);
$droot = $pbconf['required']['pbroot'];
$logpath = $pbconf['required']['logpath'];
$secret = $pbconf['required']['secret'];
$dbauth = $pbconf['database'];
$timezone = $pbconf['index_options']['timezone'];
$timestring = $pbconf['index_options']['timestring'];
$fileexclude = $pbconf['index_options']['fileexclude'];
$folderexclude = $pbconf['index_options']['folderexclude'];
$mimedebug = boolinate($pbconf['index_options']['mimedebug']);
$sorttype = $pbconf['index_options']['sortby'];
$sortdir = $pbconf['index_options']['direction'];
include "plopbox/images/mimetypes/iconindex";

// Setup timezone
if (!empty($timezone)) {
  date_default_timezone_set($timezone);
} else if (empty($timezone)){
  $logmsg3 .= ' ERROR: $timezone variable not set in pbconf.php! Defaulting to UTC.';
  date_default_timezone_set("UTC");
}

// Stop execution if any vital extensions/variables are unloaded/undefined
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
  $logmsg .= ' ERROR: $secret variable not set in pbconf.ini!';
  $logmsg2 .= ' ERROR: $secret variable not set in pbconf.ini!<br>';
  $stoperror = true;
}
if (empty($droot)) {
  $logmsg .= 'ERROR: $droot variable not set in pbconf.ini!';
  $logmsg2 .= 'ERROR: $droot variable not set in pbconf.ini!<br>';
  $stoperror = true;
}
if (session_status() == PHP_SESSION_DISABLED) {
  $logmsg .= ' ERROR: PHP sessions are disabled!';
  $logmsg2 .= ' ERROR: PHP sessions are disabled!<br>';
  $stoperror = true;
}
if (empty($logpath)) {
  $logmsg .= ' ERROR: $logpath variable not set in pbconf.ini!';
  $logmsg2 .= ' ERROR: $logpath variable not set in pbconf.ini!<br>';
  syslog(LOG_ERR, 'PlopBox:' . $logmsg);
  echo json_encode(array("opcode" => "LoginPage", "statcode" => "Error", "failmsg" => $logmsg2));
  exit();
}
if ($stoperror == true) {
  @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
  echo json_encode(array("opcode" => "LoginPage", "statcode" => "Error", "failmsg" => $logmsg2));
  exit();
}

// Stop execution if the URI contains an excluded directory
if (preg_match($folderexclude, $interlink) === 1) {
  $logmsg .= ' ACCESS DENIED';
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
  echo json_encode(array('opcode' => '403'));
  exit;
}

// Revert to defaults if any pbconf variables are missing/invalid
if (!isset($pbconf['index_options']['sortby']) or (!isset($pbconf['index_options']['direction'])) or ($pbconf['index_options']['sortby'] !== 'name' or 'date' or 'size') or ($pbconf['index_options']['direction'] !== 'ascending' or 'descending')) {
  $sortscheme = 0;
  $sorttype = 'name';
  $sortdir = 'ascending';
}
if (!isset($timestring)) {
  $timestring = "d.m.Y h:ia";
}
if (!isset($mimedebug)) {
  $mimedebug = false;
}

// Parse URI arguments
// Logout
$logout = !empty($_GET['logout']);

// Start File (For pagination)
if (!empty($_GET['start']) && ctype_digit($_GET['start'])) {
  $fstart = (int)$_GET['start'];
  $startlink = '&start=' . $fstart;
} else {
  $fstart = 0;
  $startlink = '';
}

// Initialize database connection
try {
  $db = new PDO("mysql:host=" . $dbauth['dbhost'] . ";dbname=" . $dbauth['dbname'],$dbauth['dbusername'],$dbauth['dbpassword']) or die("Database Error!");
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
  $logmsg = ' ' . $e;
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
  $db = null;
  $sth = null;
  exit();
}

// Construct initial illegal token/userid hash tables
try {
  $db->exec('CREATE TABLE IF NOT EXISTS illegal_tokens (tokenhash TEXT, born INT)');
  $db->exec('CREATE TABLE IF NOT EXISTS illegal_uids (uidhash TEXT, born INT)');
}
catch(PDOException $e) {
  $logmsg = ' ' . $e;
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
  $db = null;
  $sth = null;
  exit();
}

// Remove expired illegal token/uid hashes from the illegal lists
//(1 in 5 chance)
gc($db, $logpath);


// Start session
if (session_status() == PHP_SESSION_NONE) {
  session_set_cookie_params(1800, '/', $_SERVER['SERVER_NAME'], false, true);
  session_start();
  if (session_status() == PHP_SESSION_ACTIVE) {
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
      if (valstoken($db, session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, $logpath, 1800) === false) {
        $_SESSION['stoken'] = false;
      }
    }
    if ($_SESSION['uid'] !== false) {
      if (valuid($db, $_SESSION['uid'], $_SESSION['user'], $secret, $logpath, 1800) === 'invalid') {
        $_SESSION['uid'] = false;
      }
    }
  } else {
    $logmsg .= ' FATAL ERROR: Could not start browser session!';
    echo json_encode(array("failmsg" => 'FATAL ERROR: Could not start browser session!', "opcode" => "LoginPage", "statcode" => "Error"));
    exit;
  }
}

// State Controller
if (session_status() == PHP_SESSION_ACTIVE) {

  if ($logout === true) {
    logout($db, $logpath, $logmsg);
    header('Location: /');
    exit;
  }

  // Check if user is logged in
  if (valstoken($db, session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, $logpath, 1800) === true) {
    // Check user permissions
    if (($perm = valuid($db, $_SESSION['uid'], $_SESSION['user'], $secret, $logpath, 1800)) !== 'invalid') {

      // Send a requested file to the client
      if (!isset($_GET['dl'])) {
        if (is_file($droot . $interlink) && file_exists($droot . $interlink)) {
          if ($perm[4] === true) {
            if (preg_match($folderexclude, $interlink) or preg_match($fileexclude, $file) === 1) {
              $logmsg .= ' ACCESS DENIED';
              @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
              echo json_encode(array('opcode' => '403'));
              exit;
            } else {
              $_SESSION['dl'] = $droot . $interlink;
              header('Location: /?dl=' . newtoken(session_id(), $_SESSION['dl'], $secret));
              exit;
            }
          } else {
            $logmsg .= 'FILE OPERATION FAILURE: User "' . $_SESSION['user'] . '" is not allowed to download files.';
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
            echo json_encode(array('opcode' => '403'));
            exit;
          }
        }
      } else if (isset($_GET['dl']) && isset($_SESSION['dl'])) {
        if (valtoken($db, session_id(), $_GET['dl'], $_SESSION['dl'], $secret, $logpath, 10)) {
          retire($db, 'token', $_GET['dl'], $logpath);
          header('Content-Description: File Transfer');
          header('Content-Type: ' . strstr(finfo_file(finfo_open(FILEINFO_MIME), $_SESSION['dl']), ';', true));
          header('Content-Disposition: attachment; filename="' . basename($_SESSION['dl']) . '"');
          header('Expires: 0');
          header('Cache-Control: must-revalidate');
          header('Pragma: public');
          header('Content-Length: ' . filesize($_SESSION['dl']));
          ob_end_flush();
          readfile($_SESSION['dl']);
          $_SESSION['dl'] = null;
          exit;
        }
      }

      // Execute any file operations
      if (isset($_POST['ftoken'])) {
        if ($perm[2] && $perm[1] && $perm[0] === true) {
          $ctoken = newtoken(session_id(), 'FMANAGER', $secret);
          require "plopbox/php/filemanager.php";
        } else {
          echo json_encode(array("failmsg" => "Error: You are not allowed to modify files.", "opcode" => "FileIndex", "statcode" => "Error"));
          $logmsg .= ' FILE OPERATION FAILURE: User "' . $_SESSION['user'] . '" is not allowed to modify files.';
        }
      }

      // load Settings Page ...
      if (isset($_POST['atoken'])) {
        if ($perm[0] && $perm[1] === true) {
          $ctoken = newtoken(session_id(), 'ADMIN', $secret);
          require "plopbox/php/admin.php";
        } else {
          header('Location: /');
          echo msg('noperm_settings');
          exit;
        }
      } else {

        // ... or Load Index Core
        $ctoken = newtoken(session_id(), 'CORE', $secret);
        require "plopbox/php/core.php";

      }
    } else {
      // User is logged in with an invalid UID.
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
      logout($db, $logpath, $logmsg, ': UID for User "' . $_SESSION['user'] . '" is invalid. Logging user out.');
      echo json_encode(array("opcode" => "LoginPage", "statcode" => "Error", "failmsg" => "You have been logged out by the server."));
      exit;
    }
  } else {

    // Because user is not logged in, Load Login Page
    $ctoken = newtoken(session_id(), 'LPAGE', $secret);
    require "plopbox/php/login.php";
  }
}

// Write to access log
@file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
?>
