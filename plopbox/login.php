<?php
// PlopBox FileBrowser Login Module

// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/login.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    header("HTTP/1.0 403 Forbidden");
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken($db, session_id(), $ctoken, 'LPAGE', $secret, 10) === false) {
        $logmsg .= " LOGIN PAGE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        $_SESSION['stoken'] = false;
        header("HTTP/1.0 403 Forbidden");
        exit;
      } else if (valtoken($db, session_id(), $ctoken, 'LPAGE', $secret, 10) === true) {
        $ctoken = null;

        // Construct initial user table
        try {
          $db->exec('CREATE TABLE IF NOT EXISTS users (uid TEXT PRIMARY KEY, uname TEXT, phash TEXT, flimit INTEGER)');
        } catch(PDOException $e) {
          $logmsg = ' ' . $e;
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          $db = null;
          $sth = null;
          exit($e);
        }

        // Verify Primary Administrator exists
        $pu = 0;
        try {
          $sth = $db->query('SELECT uid FROM users');
          while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['uid'])) {
              $uid = explode('-', $row['uid'])[2];
              if ($uid === '1/1/1/1/1/1/1/1/1') {
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
        } catch(PDOException $e) {
          $logmsg = ' ' . $e;
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          $db = null;
          $sth = null;
          exit($e);
        }

        // Verify Username Exists
        function finduname($db, $u, $logpath) {
          try {
            $result = false;
            $sth = $db->prepare('SELECT uname FROM users WHERE uname=:u');
            $sth->bindValue(':u', $u, PDO::PARAM_STR);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
              if (isset($row['uname'])) {
                if ($row['uname'] === $u) {
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
            $sth = null;
            return $result;
          } catch(PDOException $e) {
            $logmsg = ' ' . $e;
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
            $db = null;
            $sth = null;
            exit($e);
          }
        }

        // Find Password Hash, Role, and Page Item Limit for given Username
        function fetchuserdata($db, $u, $logpath) {
          try {
            $result = false;
            $sth = $db->prepare('SELECT uid, uname, phash, flimit FROM users WHERE uname=:u');
            $sth->bindValue(':u', $u, PDO::PARAM_STR);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
              if (isset($row['uname'])) {
                if (isset($row['uid']) && isset($row['uname']) && isset($row['phash']) && isset($row['flimit'])) {
                  if ($row['uname'] == $u) {
                    $sth = null;
                    $result = array('uid' => $row['uid'], 'uname' => $row['uname'], 'phash' => $row['phash'], 'flimit' => $row['flimit']);
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
            $sth = null;
            return $result;
          } catch(PDOException $e) {
            $logmsg = ' ' . $e;
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
            $db = null;
            $sth = null;
            exit($e);
          }
        }

        // Create Primary User
        function createpu($db, $u, $p, $logpath, $secret) {
          try {
            $sth = $db->prepare('INSERT INTO users (uid, uname, phash, flimit) VALUES (:uid, :uname, :phash, 50)');
            $sth->bindValue(':uid', newuid($db, $u, '1/1/1/1/1/1/1/1/1', $logpath, $secret));
            $sth->bindValue(':uname', $u);
            $sth->bindValue(':phash', password_hash($p, PASSWORD_BCRYPT));
            $sth->execute();
            $sth = null;
          } catch(PDOException $e) {
            $logmsg = ' ' . $e;
            @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
            $db = null;
            $sth = null;
            exit($e);
          }
        }

        // Login Page
        function loginpage($host, $secret, $simplemode) {
          $token = newtoken(session_id(), 'LDATA', $secret);
          if ($simplemode == false) {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>Log In</title>
            <link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon">
            <link rel="stylesheet" type="text/css" href="' . $host . '/plopbox/style.css">
            <meta name="viewport" content="width=300, minimum-scale=0.5, maximum-scale=1.0, user-scalable=no">
            <script type="text/javascript">
            function msgclose() {
              document.getElementById("msg").style.visibility = "hidden";
            }
            </script>
            </head>';
            echo '<div class="loginpage">
            <div align="Center" id="loginbox" class="loginbox">
            <div class="loginlogo">plopbox</div>
            <div class="loginboxwrapper">';
            echo '<form class="loginform" action="' . $host . '" method="post" enctype="multipart/form-data">
            <input type="hidden" name="token" value="' . $token . '">
            <ul><li><label for="username">Username</label><input type="text" name="username" id="username"><span>Forgot your Username?</span></li>
            <li><label for="password">Password</label><input type="password" name="password" id="password"><span>Forgot your Password?</span></li>
            <li><input type="submit" value="Log In"></li></ul></form>
            </div></div></div>';
            echo '<div class="loginpagebackground"></div>
            <div class="clouds"></div>
            <a class="simplemodelogin" href="/?simple=true">Activate Simple Mode (Turn CSS &amp; JS Off)</a>';
          } else if ($simplemode == true) {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>Log In</title><link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon"></head>';
            echo 'Log In<br><form action="' . $host . '/?simple=1" method="post" enctype="multipart/form-data">
            <input type="hidden" name="token" value="' . $token . '">
            <ul><li><label for="username">Username</label><input type="text" name="username" id="username"><span>Forgot your Username?</span></li>
            <li><label for="password">Password</label><input type="password" name="password" id="password"><span>Forgot your Password?</span></li>
            <li><input type="submit" value="Log In"></li></ul></form>
            <a class="simplemodelogin" href="/?simple=false">Deactivate Simple Mode (Turn CSS &amp; JS On)</a>';
          }
          $token = null;
        }
        // Primary User Creation Page
        function createpupage($host, $secret, $simplemode) {
          $putoken = newtoken(session_id(), 'PUDATA', $secret);
          if ($simplemode == false) {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>Create Primary User</title>
            <link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon">
            <link rel="stylesheet" type="text/css" href="' . $host . '/plopbox/style.css">
            <meta name="viewport" content="width=300, minimum-scale=0.5, maximum-scale=1.0, user-scalable=no">
            <script type="text/javascript">
            function msgclose() {
              document.getElementById("msg").style.visibility = "hidden";
            }
            </script>
            </head>';
            echo '<div class="loginpage">
            <div align="Center" id="createpubox" class="loginbox"><div class="loginlogo">plopbox</div>';
            echo '<div class="loginboxwrapper">
            Create Primary User Account<form class="loginform" action="' . $host . '" method="post" enctype="multipart/form-data">
            <input type="hidden" name="putoken" value="' . $putoken . '">
            <ul><li><label for="username">Username</label><input type="text" name="puusername" id="puusername"></li>
            <li><label for="password">Password</label><input type="password" name="pupassword" id="pupassword"></li>
            <li><input type="submit" value="Create User"></li></ul></form></div></div></div>';
            echo '<div class="loginpagebackground"></div>
            <div class="clouds"></div>
            <a class="simplemodelogin" href="/?simple=true">Activate Simple Mode (Turn CSS &amp; JS Off)</a>';
          } else if ($simplemode == true) {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>Create Primary User</title>
            <link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon"></head>';
            echo 'Create Primary User Account<br><form action="' . $host . '/?simple=1" method="post" enctype="multipart/form-data">
            <input type="hidden" name="putoken" value="' . $putoken . '">
            <ul><li><label for="username">Username</label><input type="text" name="puusername" id="puusername"></li>
            <li><label for="password">Password</label><input type="password" name="pupassword" id="pupassword"></li>
            <li><input type="submit" value="Create User"></li></ul></form>
            <a class="simplemodelogin" href="/?simple=false">Deactivate Simple Mode (Turn CSS &amp; JS On)</a>';
          }
          $putoken = null;
        }

        // "Log In" Dialog Box
        if ($pu == true) {
          if (!empty($_POST['username']) && !empty($_POST['password']) == true) {
            if (!empty($_POST['token'])) {
              if (valtoken($db, session_id(), $_POST['token'], 'LDATA', $secret, $logpath, 300) == true) {
                if (finduname($db, $_POST['username'], $logpath) !== false) {
                  $udata = fetchuserdata($db, $_POST['username'], $logpath);
                  if (password_verify($_POST['password'], $udata['phash'])) {
                    if (valuid($db, $udata['uid'], $udata['uname'], $secret, $logpath) !== 'invalid') {
                      $_SESSION['uid'] = recalcuid($db, $udata['uid'], $udata['uname'], $secret, $logpath, true);
                      $perm = valuid($db, $_SESSION['uid'], $udata['uname'], $secret, $logpath, 1800);
                      if ($perm[0] === true) {
                        $_SESSION['flimit'] = $udata['flimit'];
                        $_SESSION['user'] = $udata['uname'];
                        $_SESSION['stoken'] = newstoken(session_id(), $_SESSION['uid'], $secret);
                        $logmsg4 .= ' LOGIN PAGE, AUTH OK: User "' . $_SESSION['user'] . '" logged in successfully.';
                        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg4 . PHP_EOL, FILE_APPEND) or logerror();
                        Header("Location: " . $host);
                        exit;
                      } else {
                        $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                        echo msg('custom', 'failmsg', 'Error: You are not allowed to log in.');
                        loginpage($host, $secret, $simplemode);
                        $logmsg .= ' LOGIN PAGE, AUTH FAILURE: User "' . $_POST['username'] . '" is not allowed to log in.';
                      }
                    } else {
                      $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                      echo msg('custom', 'failmsg', 'FATAL DATABASE ERROR: Unable to log in.');
                      loginpage($host, $secret, $simplemode);
                      $logmsg .= ' LOGIN PAGE, FATAL DATABASE ERROR: UID for User "' . $_POST['username'] . '" has been tampered with! You must delete the user.';
                    }
                  } else {
                    $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                    echo msg('badlogin');
                    loginpage($host, $secret, $simplemode);
                    $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Wrong password for user "' . $_POST['username'] . '".';
                  }
                } else {
                  $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                  echo msg('badlogin');
                  loginpage($host, $secret, $simplemode);
                  $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Username "' . $_POST['username'] . '" does not exist.';
                }
              } else if (valtoken($db, session_id(), $_POST['token'], 'LDATA', $secret, 300) == false) {
                $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                echo msg('badtoken');
                loginpage($host, $secret, $simplemode);
                $logmsg .= " LOGIN PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
              }
            } else {
              $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
              $logmsg .= " LOGIN PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
              @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
              header("HTTP/1.0 403 Forbidden");
              exit;
            }
          } else if ($_SESSION['stoken'] === false) {
            loginpage($host, $secret, $simplemode);
            $logmsg .= " LOGIN PAGE, OK";
          }

          // "Create Primary Administrator" Dialog Box
        } else if ($pu == false) {
          if (!empty($_POST['puusername']) && !empty($_POST['pupassword'])) {
            if (!empty($_POST['putoken'])) {
              if (valtoken($db, session_id(), $_POST['putoken'], 'PUDATA', $secret, $logpath, 300) == true) {
                createpu($db, $_POST['puusername'], $_POST['pupassword'], $secret, $logpath);
                echo msg('custom', 'msg', 'Primary Administrator Account Created!');
                loginpage($host, $secret, $simplemode);
                $logmsg .= " LOGIN PAGE, OK: Primary User created!";
              } else if (valtoken($db, session_id(), $_POST['putoken'], 'PUDATA', $secret, $logpath, 300) == false) {
                $_SESSION['stoken'] == false;
                $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
                echo msg('badtoken');
                Header('Location: ' . $host);
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
} else {
  header("HTTP/4.01 403 Forbidden");
  exit;
}
?>
