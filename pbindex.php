<?php
// PlopBox FileBrowser Controller Core

// Initialize core variables
$funcload = 1;
require "plopbox/php/pbfunc.php";
$privateFolder = "/private_htdocs";
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
// Set Filesystem path
$fspath = $droot . $privateFolder . $interlink;
$logpath = $pbconf['required']['logpath'];
$secret = $pbconf['required']['secret'];
$dbauth = $pbconf['database'];
$timezone = $pbconf['index_options']['timezone'];
$timestring = $pbconf['index_options']['timestring'];
$fileexclude = $pbconf['index_options']['fileexclude'];
$folderexclude = $pbconf['index_options']['folderexclude'];
$mimedebug = boolinate($pbconf['index_options']['mimedebug']);
$sorttype = $pbconf['index_options']['sort'];
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

// Load Medoo
require "plopbox/php/medoo/medoo.php";

// Initialize Medoo database object
$db = new medoo([
  'database_type' => 'mysql',
  'database_name' => $dbauth['dbname'],
  'server' => $dbauth['dbhost'],
  'username' => $dbauth['dbusername'],
  'password' => $dbauth['dbpassword'],
  'charset' => 'utf8',
  'option' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
]);

// Construct users table
$db->query("CREATE TABLE IF NOT EXISTS users (uid TEXT, uname TEXT, phash TEXT, flimit INTEGER)");
// Construct token blacklist table
$db->query("CREATE TABLE IF NOT EXISTS blacklist (data TEXT, born INTEGER)");

// Blacklist GC (1 in 5 chance)
if (mt_rand(0, 5) === 1) {
  $db->delete("blacklist",
  ["born[<]" => (time() - 1800)]);
}

// Load JWT Library
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Keychain;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;

// JWT Validation error
function jwtError($logpath, $logmsg) {
  $logmsg .= ' ERROR: Invalid client token. Could not start user session.';
  echo json_encode(array("failmsg" => ' ERROR: Invalid client token. Could not start user session.', "opcode" => "LoginPage", "statcode" => "Error"));
  @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
  exit;
}

// Process a Session JWT from a client
$startSession = false;
if (isset($_POST['token'])) {
  // Parse JWT
  $jwtToken = (new Parser())->parse((string) $_POST['token']);
  $jwtToken->getHeaders();
  $jwtToken->getClaims();
  // Lookup UID from JWT Claim
  if ($jwtToken->getClaim('uid') === $db->select("users", ["uid[=]" => $jwtToken->getClaim('uid')])[0]) {
    // Set JWT Validation Data
    $jwtData = new ValidationData();
    $jwtData->setIssuer($host);
    $jwtData-setAudience($_SERVER['REMOTE_ADDR']);
    $jwtData->setId($jwtToken->getHeader('jti'));
    // Validate JWT
    if ($jwtToken->validate($jwtData) === true) {
      // Validate Session Token
      if (valsid($db, $jwtToken->getClaim('uid'), $jwtToken->getClaim('sid'), $secret, 1800) === true) {
        // Validate User ID & Permissions
        if (($perm = valuid($db, $jwtToken->getClaim('uid'), $jwtToken->getClaim('username'), $secret, 1800)) !== 'invalid') {
          $startSession = true;
        }
      }
    }
  }
}

// No valid Session JWT - No Session
if ($startSession === false) {
  // Load Login Page
  $ctoken = newtoken(session_id(), 'LPAGE', $secret);
  require "plopbox/php/login.php";

  // Valid Session JWT - Start Session
} else if ($startSession === true) {

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
          $_SESSION['dl'] = $droot . $privateFolder . $interlink;
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
  }

  // Load Index Core
  $ctoken = newtoken(session_id(), 'CORE', $secret);
  require "plopbox/php/core.php";

}

// Write to access log
@file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
?>
