<?php
// PlopBox FileBrowser Login Module

// Check Core Mothership Token
if (!function_exists('valtoken')) {
  $_SESSION['valid'] = false;
  syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/login.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
  header("HTTP/1.0 403 Forbidden");
  exit;
} else if (function_exists('valtoken')) {
  if (!empty($ctoken)) {
  if (valtoken($ctoken, $secret, 10) == false) {
    $logmsg .= " LOGIN PAGE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
    @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
    $_SESSION['valid'] = false;
    header("HTTP/1.0 403 Forbidden");
    exit;
  } else if (valtoken($ctoken, $secret, 10) == true) {
    $ctoken = null;

// Construct initial database
try {
  $db = new PDO("sqlite:" . $droot . "/plopbox/db/users.db") or die("Database Error!");
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->exec(
  'CREATE TABLE IF NOT EXISTS users (uname TEXT PRIMARY KEY, prim INTEGER, phash TEXT)');
  $db = null;
}
catch(PDOException $e) {
  $logmsg = ' ' . $e;
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  $db = null;
  $sth = null;
  exit($e);
}

// Verify Primary User exists
$pu = 0;
try {
  $db = new PDO("sqlite:" . $droot . "/plopbox/db/users.db") or die("Database Error!");
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $sth = $db->query('SELECT prim FROM users WHERE prim = 1');
  $sth->setFetchMode(PDO::FETCH_ASSOC);
  while ($row = $sth->fetch()) {
    if (isset($row['prim'])) {
      if ($row['prim'] == 1) {
        $pu = 1;
        break;
      } else {
        continue;
      }
    } else {
      continue;
    }
  }
  $db = null;
  $sth = null;
}
catch(PDOException $e) {
  $logmsg = ' ' . $e;
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  $db = null;
  $sth = null;
  exit($e);
}

// Login Page
function loginpage($host, $secret) {
  $token = newtoken($secret);
  echo '<link rel="shortcut icon" href="/plopbox/icons/favicon.gif" type="image/x-icon"/>';
  echo '<link rel="stylesheet" type="text/css" href=' . $host . '/plopbox/style.css />';
  echo '<div style="background-image:url("/plopbox/images/controls/login.png")" class="wrapper"><div align="Center" id="loginbox" class="dialogbox">';
  echo '<div class="titlebar">Log In</div>';
  echo '<div class="boxwrapper">';
  echo '<form action="' . $host . '" method="post" enctype="multipart/form-data"><input type="hidden" name="token" value="' . $token . '">Username:<input type="text" name="username" id="username"><br>Password:<input type="text" name="password" id="password"><br><input type="submit" value="Log In" name="login"></form></div></div></div>';
  $token = null;
}
// Primary User Creation Page
function createpupage($host, $secret) {
  $putoken = newtoken($secret);
  echo '<link rel="shortcut icon" href="/plopbox/icons/favicon.gif" type="image/x-icon"/>';
  echo '<link rel="stylesheet" type="text/css" href=' . $host . '/plopbox/style.css />';
  echo '<div style="background-image:url("/plopbox/images/controls/login.png")" class="wrapper"><div align="Center" id="createpubox" class="dialogbox">';
  echo '<div class="titlebar">Create Primary Account</div>';
  echo '<div class="boxwrapper">';
  echo '<form action="' . $host . '" method="post" enctype="multipart/form-data"><input type="hidden" name="putoken" value="' . $putoken . '">Username:<input type="text" name="puusername" id="puusername"><br>Password:<input type="text" name="pupassword" id="pupassword"><br><input type="submit" value="Create User" name="createpu"></form></div></div></div>';
  $putoken = null;
}

// Verify Username Exists
function finduname($u, $droot, $logpath) {
  try {
    $db = new PDO("sqlite:" . $droot . "/plopbox/db/users.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sth = $db->prepare('SELECT uname FROM users WHERE uname = :u');
    $sth->bindParam(':u', $u);
    $sth->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $sth->fetch()) {
      echo 'TEST ' . $row['uname'];
      if ($row['uname'] == $u) {
        $db = null;
        $sth = null;
        return true;
        break;
      } else {
        continue;
      }
    }
    $db = null;
    $sth = null;
    return false;
  }
  catch(PDOException $e) {
    $logmsg = ' ' . $e;
    @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
    $db = null;
    $sth = null;
    return false;
    exit($e);
  }
}

// Find Password Hash for given Username
function findphash($u, $droot, $logpath) {
  try {
    $db = new PDO("sqlite:" . $droot . "/plopbox/db/users.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sth = $db->prepare('SELECT uname, phash FROM users WHERE uname = :u');
    $sth->bindParam(':u', $u);
    $sth->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $sth->fetch()) {
      if ($row['uname'] == $u) {
        $phash = $row['phash'];
        $db = null;
        $sth = null;
        return $phash;
        break;
      } else {
        continue;
      }
    }
    $db = null;
    $sth = null;
    return false;
  }
  catch(PDOException $e) {
    $logmsg = ' ' . $e;
    @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
    $db = null;
    $sth = null;
    return false;
    exit($e);
  }
}

// Create Primary User
function createpu($u, $p, $droot, $logpath) {
  try {
    $phash = password_hash(dechex(time()) . $p, PASSWORD_BCRYPT);
    $db = new PDO("sqlite:" . $droot . "/plopbox/db/users.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sth = $db->prepare('INSERT INTO users (uname, prim, phash) VALUES (:uname, 1, :phash)');
    $sth->bindParam(':uname', $u);
    $sth->bindParam(':phash', $p);
    $sth->execute();
    $db = null;
    $sth = null;
  }
  catch(PDOException $e) {
    $logmsg = ' ' . $e;
    @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
    $db = null;
    $getdb = null;
    exit($e);
  }
}

// "Log In" Dialog Box
if ($pu == true) {
  if (!empty($_POST['username']) && !empty($_POST['password']) == true) {
    if (!empty($_POST['token'])) {
      if (valtoken($_POST['token'], $secret, 300) == true) {
        if (finduname($_POST['username'], $droot, $logpath) == true) {
          if (findphash($_POST['username'], $droot, $logpath) !== false ) {
            if (password_verify($_POST['password'], findphash($_POST['username']))) {
              $_SESSION['user'] = $_POST['username'];
              $_SESSION['timeout'] = time();
              $_SESSION['valid'] = true;
            } else {
              $_SESSION['valid'] = false;
              echo '<div class="loginfailure">You have entered an incorrect username or password.</div>';
              loginpage($host, $secret);
              $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Wrong password for user "' . $_POST['username'] . '".';
            }
          } else {
            $_SESSION['valid'] = false;
            echo '<div class="loginfailure">You have entered an incorrect username or password.</div>';
            $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Password hash not found for user "' . $_POST['username'] . '", possible database corruption!';
          }
        } else {
          $_SESSION['valid'] = false;
          echo '<div class="loginfailure">You have entered an incorrect username or password.</div>';
          loginpage($host, $secret);
          $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Username "' . $_POST['username'] . '" does not exist.';
        }
      } else if (valtoken($_POST['token'], $secret, 300) == false) {
        $_SESSION['valid'] == false;
        echo '<div class="loginfailure">Error: Invalid/Expired Token</div>';
        loginpage($host, $secret);
        $logmsg .= " LOGIN PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
      }
    } else {
      $_SESSION['valid'] == false;
      $logmsg .= " LOGIN PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
      header("HTTP/1.0 403 Forbidden");
      exit;
    }
  } else if ($_SESSION['valid'] == false) {
    loginpage($host, $secret);
    $logmsg .= " LOGIN PAGE, OK";
  }

  // "Create Primary User" Dialog Box
} else if ($pu == false) {
  if (!empty($_POST['puusername']) && !empty($_POST['pupassword'])) {
    if (!empty($_POST['putoken'])) {
      if (valtoken($_POST['putoken'], $secret, 300) == true) {
        createpu($_POST['puusername'], $_POST['pupassword'], $droot, $logpath);
        loginpage($host, $secret);
        $logmsg .= " LOGIN PAGE, OK: Primary User created!";
      } else if (valtoken($_POST['putoken'], $secret, 300) == false) {
        $_SESSION['valid'] == false;
        $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
        echo '<div class="loginfailure">Error: Invalid/Expired Token</div>';
        createpupage($host, $secret);
      }
    } else {
      $_SESSION['valid'] == false;
      $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
      header("HTTP/1.0 403 Forbidden");
      exit;
    }
  } else {
    createpupage($host, $secret);
    $logmsg .= " PRIMARY USER CREATION PAGE, OK";
  }
}
}
} else {
  $logmsg .= " LOGIN PAGE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  $_SESSION['valid'] = false;
  header("HTTP/1.0 403 Forbidden");
  exit;
}
}
?>
