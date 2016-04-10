<?php
// PlopBox FileBrowser Login Module

// Check Core Mothership Token
if (!function_exists('valtoken')) {
  $_SESSION['stoken'] = false;
  syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/login.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
  header("HTTP/1.0 403 Forbidden");
  exit;
} else if (function_exists('valtoken')) {
  if (!empty($ctoken)) {
    if (valtoken($ctoken, $secret, 10) == false) {
      $logmsg .= " LOGIN PAGE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
      $_SESSION['stoken'] = false;
      header("HTTP/1.0 403 Forbidden");
      exit;
    } else if (valtoken($ctoken, $secret, 10) == true) {
      $ctoken = null;

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

      // Construct initial user table
      try {
        $db->exec('CREATE TABLE IF NOT EXISTS users (uname TEXT PRIMARY KEY, role INTEGER, phash TEXT, flimit INTEGER)');
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
        $sth = $db->query('SELECT role FROM users WHERE role = 1');
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
          if (isset($row['role'])) {
            if ($row['role'] == 1) {
              $pu = 1;
              break;
            } else {
              continue;
            }
          } else {
            continue;
          }
        }
        $sth = null;
      }
      catch(PDOException $e) {
        $logmsg = ' ' . $e;
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        $db = null;
        $sth = null;
        exit($e);
      }

      // Verify Username Exists
      function finduname($db, $u, $droot, $logpath) {
        try {
          $result = false;
          $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $sth = $db->prepare('SELECT uname FROM users WHERE uname=:u');
          $sth->bindValue(':u', $u, PDO::PARAM_STR);
          $sth->execute();
          while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['uname'])) {
              if ($row['uname'] == $u) {
                $db = null;
                $sth = null;
                $result = true;
                break;
              } else {
                continue;
              }
            } else {
              continue;
            }
          }
          return $result;
          $sth = null;
        }
        catch(PDOException $e) {
          $logmsg = ' ' . $e;
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          $db = null;
          $sth = null;
          exit($e);
        }
      }

      // Find Password Hash, Role, and Page Item Limit for given Username
      function fetchuserdata($db, $u, $droot, $logpath) {
        try {
          $result = false;
          $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $sth = $db->prepare('SELECT uname, role, phash, flimit FROM users WHERE uname=:u');
          $sth->bindValue(':u', $u, PDO::PARAM_STR);
          $sth->execute();
          while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['uname'])) {
              if (isset($row['phash']) && isset($row['role']) && isset($row['flimit'])) {
                if ($row['uname'] == $u) {
                  $sth = null;
                  $result = array('uname' => $row['uname'], 'role' => $row['role'], 'phash' => $row['phash'], 'flimit' => $row['flimit']);
                  break;
                } else {
                  continue;
                }
              } else {
                continue;
              }
            } else {
              $result = false;
            }
          }
          return $result;
          $sth = null;
        }
        catch(PDOException $e) {
          $logmsg = ' ' . $e;
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          $db = null;
          $sth = null;
          exit($e);
        }
      }

      // Create Primary User
      function createpu($db, $u, $p, $droot, $logpath) {
        try {
          $phash = password_hash($p, PASSWORD_BCRYPT);
          $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $sth = $db->prepare('INSERT INTO users (uname, role, phash, flimit) VALUES (:uname, 1, :phash, 50)');
          $sth->bindValue(':uname', $u);
          $sth->bindValue(':phash', $phash);
          $sth->execute();
          $sth = null;
        }
        catch(PDOException $e) {
          $logmsg = ' ' . $e;
          @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
          $db = null;
          $sth = null;
          exit($e);
        }
      }

      // Login Page
      function loginpage($host, $secret, $simplemode) {
        $token = newtoken($secret);
        if ($simplemode == 0) {
          echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">';
        echo '<head><title>Log In</title><link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon">';
        echo '<link rel="stylesheet" type="text/css" href="' . $host . '/plopbox/style.css"></head>';
        echo '<div class="loginpage"><div align="Center" id="loginbox" class="loginbox"><div class="loginlogo">plopbox</div>';
        echo '<div class="loginboxwrapper">';
        echo '<form class="loginform" action="' . $host . '" method="post" enctype="multipart/form-data"><input type="hidden" name="token" value="' . $token . '"><ul><li><label for="username">Username</label><input type="text" name="username" id="username"><span>Forgot your Username?</span></li><li><label for="password">Password</label><input type="password" name="password" id="password"><span>Forgot your Password?</span></li><li><input type="submit" value="Log In"></li></ul></form></div></div></div>';
        echo '<div class="loginpagebackground"></div>';
        echo '<div class="clouds"></div>';
      } else if ($simplemode == 1) {
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">';
        echo '<head><title>Log In</title><link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon"></head>';
        echo 'Log In<br><form action="' . $host . '/?simple=1" method="post" enctype="multipart/form-data"><input type="hidden" name="token" value="' . $token . '"><ul><li><label for="username">Username</label><input type="text" name="username" id="username"><span>Forgot your Username?</span></li><li><label for="password">Password</label><input type="password" name="password" id="password"><span>Forgot your Password?</span></li><li><input type="submit" value="Log In"></li></ul></form>';
      }
        $token = null;
      }
      // Primary User Creation Page
      function createpupage($host, $secret, $simplemode) {
        $putoken = newtoken($secret);
        if ($simplemode == 0) {
          echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">';
        echo '<head><title>Create Primary User</title><link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon">';
        echo '<link rel="stylesheet" type="text/css" href="' . $host . '/plopbox/style.css"></head>';
        echo '<div class="loginpage"><div align="Center" id="createpubox" class="loginbox"><div class="loginlogo">plopbox</div>';
        echo '<div class="loginboxwrapper">';
        echo 'Create Primary User Account<form class="loginform" action="' . $host . '" method="post" enctype="multipart/form-data"><input type="hidden" name="putoken" value="' . $putoken . '"><ul><li><label for="username">Username</label><input type="text" name="puusername" id="puusername"></li><li><label for="password">Password</label><input type="password" name="pupassword" id="pupassword"></li><li><input type="submit" value="Create User"></li></ul></form></div></div></div>';
        echo '<div class="loginpagebackground"></div>';
        echo '<div class="clouds"></div>';
      } else if ($simplemode == 1) {
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">';
        echo '<head><title>Create Primary User</title><link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon"></head>';
        echo 'Create Primary User Account<br><form action="' . $host . '/?simple=1" method="post" enctype="multipart/form-data"><input type="hidden" name="putoken" value="' . $putoken . '"><ul><li><label for="username">Username</label><input type="text" name="puusername" id="puusername"></li><li><label for="password">Password</label><input type="password" name="pupassword" id="pupassword"></li><li><input type="submit" value="Create User"></li></ul></form>';
      }
        $putoken = null;
      }

      // "Log In" Dialog Box
      if ($pu == true) {
        if (!empty($_POST['username']) && !empty($_POST['password']) == true) {
          if (!empty($_POST['token'])) {
            if (valtoken($_POST['token'], $secret, 300) == true) {
              if (finduname($db, $_POST['username'], $droot, $logpath) !== false) {
                $udata = fetchuserdata($db, $_POST['username'], $droot, $logpath);
                if (password_verify($_POST['password'], $udata['phash'])) {
                  $_SESSION['flimit'] = $udata['flimit'];
                  $_SESSION['user'] = $udata['uname'];
                  $_SESSION['stoken'] = newtoken($secret);
                  $logmsg4 .= ' LOGIN PAGE, AUTH OK: User "' . $_SESSION['user'] . '" logged in successfully.';
                  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg4 . PHP_EOL, FILE_APPEND) or logerror();
                } else {
                  $_SESSION['stoken'] = false;
                  echo '<script type="text/javascript">function msgclose() { document.getElementById("msg").style.visibility = "hidden"; }</script><div id="msg" style="visibility:visible;" class="failmsg">You have entered an incorrect username or password.<img alt="close" class="msgclose" onclick="msgclose()" src="/plopbox/images/controls/close.png"></div>';
                  loginpage($host, $secret);
                  $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Wrong password for user "' . $_POST['username'] . '".';
                }
              } else {
                $_SESSION['stoken'] = false;
                echo '<script type="text/javascript">function msgclose() { document.getElementById("msg").style.visibility = "hidden"; }</script><div id="msg" style="visibility:visible;" class="failmsg">You have entered an incorrect username or password.<img alt="close" class="msgclose" onclick="msgclose()" src="/plopbox/images/controls/close.png"></div>';
                loginpage($host, $secret, $simplemode);
                $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Username "' . $_POST['username'] . '" does not exist.';
              }
            } else if (valtoken($_POST['token'], $secret, 300) == false) {
              $_SESSION['stoken'] == false;
              echo '<div class="failmsg">Error: Invalid/Expired Token</div>';
              loginpage($host, $secret);
              $logmsg .= " LOGIN PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
            }
          } else {
            $_SESSION['stoken'] == false;
            $logmsg .= " LOGIN PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
            header("HTTP/1.0 403 Forbidden");
            exit;
          }
        } else if ($_SESSION['stoken'] == false) {
          loginpage($host, $secret, $simplemode);
          $logmsg .= " LOGIN PAGE, OK";
        }

        // "Create Primary User" Dialog Box
      } else if ($pu == false) {
        if (!empty($_POST['puusername']) && !empty($_POST['pupassword'])) {
          if (!empty($_POST['putoken'])) {
            if (valtoken($_POST['putoken'], $secret, 300) == true) {
              createpu($db, $_POST['puusername'], $_POST['pupassword'], $droot, $logpath);
              loginpage($host, $secret, $simplemode);
              $logmsg .= " LOGIN PAGE, OK: Primary User created!";
            } else if (valtoken($_POST['putoken'], $secret, 300) == false) {
              $_SESSION['stoken'] == false;
              $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
              echo '<script type="text/javascript">function msgclose() { document.getElementById("msg").style.visibility = "hidden"; }</script><div id="msg" style="visibility:visible;" class="failmsg">Error: Invalid/Expired Token<img alt="close" class="msgclose" onclick="msgclose()" src="/plopbox/images/controls/close.png"></div>';
              createpupage($host, $secret, $simplemode);
            }
          } else {
            $_SESSION['stoken'] == false;
            $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
            header("HTTP/1.0 403 Forbidden");
            exit;
          }
        } else {
          createpupage($host, $secret, $simplemode);
          $logmsg .= " PRIMARY USER CREATION PAGE, OK";
        }
      }
    }
  } else {
    $logmsg .= " LOGIN PAGE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
    @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
    $_SESSION['stoken'] = false;
    header("HTTP/1.0 403 Forbidden");
    exit;
  }
}
?>
