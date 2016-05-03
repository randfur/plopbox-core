<?php
// PlopBox User Management Module
// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/admin.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    header("HTTP/4.01 403 Forbidden");
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken($db, session_id(), $ctoken, 'ADMIN', $secret, $logpath, 10) == false) {
        $logmsg .= " SETTINGS, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        header("HTTP/4.01 403 Forbidden");
        exit;
      } else if (valtoken($db, session_id(), $ctoken, 'ADMIN', $secret, $logpath, 10) == true) {
        $ctoken = null;
        if (valstoken($db, session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, 1800) == false) {
          $logmsg .= " SETTINGS, ACCESS DENIED: INVALID/EXPIRED SESSION TOKEN";
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          retire($db, 'token', $_SESSION['stoken']);
          retire($db, 'uid', $_SESSION['uid']);
          $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
          header("HTTP/4.01 403 Forbidden");
          exit;
        } else {

          // Create New User
          function newuser($db, $u, $r, $p, $secret, $logpath) {
            try {
              $phash = password_hash($p, PASSWORD_BCRYPT);
              $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $sth = $db->prepare('INSERT INTO users (uid, uname, phash, flimit) VALUES (:uid, :uname, :phash, 50)');
              $sth->bindValue(':uid', newuid($db, $u, $r, $secret));
              $sth->bindValue(':uname', $u);
              $sth->bindValue(':phash', password_hash($p, PASSWORD_BCRYPT));
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

          // Fetch Usernames, UID's, & Password Hashes for all Users
          function fetchuman($db, $logpath) {
            try {
              $result = false;
              $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $sth = $db->prepare('SELECT uid, uname FROM users');
              $sth->execute();
              $row = $sth->fetch(PDO::FETCH_ASSOC);
              $count = 0;
              foreach ($row as $uidentry) {
                $uidresult[$count] = array('uid' => $row['uid']);
                $count++;
              }
              $count = 0;
              foreach ($row as $unameentry) {
                $unameresult[$count] = array('uname' => $row['uname']);
                $count++;
              }
              $sth = null;
              $result = array('unames' => $unameresult, 'uids' => $uidresult);
              return $result;
            }
            catch(PDOException $e) {
              $logmsg = ' ' . $e;
              @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
              $db = null;
              $sth = null;
              exit($e);
            }
          }

          // Personal Settings
          if (!isset($_POST['sstoken']) && !isset($_POST['umtoken'])) {
            if ($perm[0] && $perm[1]=== true) {
              $page = 0;
              $sel50 = $sel100 = $sel200 = $sel300 = $sel300 = $sel500 = $sel1000 = '';
              switch ($_SESSION['flimit']) {
                case 50:
                $sel50 = 'selected';
                break;
                case 100:
                $sel100 = 'selected';
                break;
                case 200:
                $sel200 = 'selected';
                break;
                case 300:
                $sel300 = 'selected';
                break;
                case 500:
                $sel500 = 'selected';
                break;
                case 1000:
                $sel1000 = 'selected';
                break;
              }
            } else {
              header('Location: ' . $host );
              echo msg(noperm_settings);
            }

            // Server Settings
          } else if (isset($_POST['sstoken']) && !isset($_POST['umtoken'])) {
            if (valtoken($db, session_id(), $_POST['sstoken'], 'SERVERSETTINGS', $secret, $logpath, 800)) {
              if ($perm[7] && $perm[8] === true) {
                $page = 1;
              } else {
                header('Location: ' . $host );
                echo msg(noperm_server);
                exit;
              }
            } else {
              $logmsg .= " SERVER SETTINGS, ACCESS DENIED: INVALID/EXPIRED TOKEN";
              @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
              header('Location: ' . $host );
              echo msg(badtoken);
              exit;
            }

            // User Management
          } else if (!isset($_POST['sstoken']) && isset($_POST['umtoken'])) {
            if ($perm[7] === true) {
              if (isset($_POST['newutoken']) && isset($_POST['newuname']) && isset($_POST['newurole']) && isset($_POST['newupass']) && !empty($_POST['newuname']) && !empty($_POST['newurole']) && !empty($_POST['newupass'])) {
                if (valtoken($db, session_id(), $_POST['newutoken'], 'USERMANAGER', $secret, $logpath, 900) === true) {
                  newuser($db, $_POST['newuname'], $_POST['newurole'], $_POST['newupass'], $secret, $logpath);
                } else {
                  $logmsg .= " USER MANAGER, NEW USER, ACCESS DENIED: INVALID/EXPIRED TOKEN";
                  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
                  header('Location: ' . $host );
                  echo msg(badtoken);
                  exit;
                }
              }
              $page = 2;
              $count = 0;
              $userentries = array();
              $pflags = array();
              $udata = fetchuman($db, $logpath);
              while ($count < count($udata['unames'][0])) {
                foreach ($udata['unames'][0] as $username) {
                  $userentries[$count] = $username;
                  $count++;
                }
                foreach ($udata['uids'][0] as $userid) {
                  if (($permissions = valuid($db, $_SESSION['uid'], $_SESSION['user'], $secret, $logpath)) !== 'invalid') {
                    $count = 0;
                    foreach ($permissions as $pflag) {
                      $pflags[$count] = '';
                      if ($pflag === true) {
                        $pflags[$count] .= ' <img src="/plopbox/images/controls/smallcheckmark.png">';
                      } else if ($pflag === false) {
                        $pflags[$count] .= ' <img src="/plopbox/images/controls/smallxmark.png">';
                      }
                      $count++;
                    }
                  }
                }
                $count++;
              }
              $count = 0;
              while ($count < count($userentries)) {
                $output .= '<div class="sentry"><div class="username">' . $userentries[$count] . '</div><div class="permflags">';
                foreach ($pflags as $flagset) {
                  $output .= $flagset;
                }
                $output .= '</div></div></div>';
                $count++;
              }

            } else {
              header('Location: ' . $host );
              echo msg(noperm_users);
            }
          }
          require "plopbox/templates/settings.php";
        }
      } else {
        $logmsg .= " SETTINGS, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        $_SESSION['stoken'] = false;
        $_SESSION['uid'] = false;
        $_SESSION['user'] = false;
        header("HTTP/4.01 403 Forbidden");
        exit;
      }
    }
  }
} else {
  header("HTTP/4.01 403 Forbidden");
  exit;
}
?>
