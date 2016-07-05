<?php
// PlopBox FileBrowser Login Module

// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/php/login.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(array('opcode' => '403'));
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken($db, session_id(), $ctoken, 'LPAGE', $secret, $logpath, 10) === false) {
        $logmsg .= " LOGIN PAGE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
        $_SESSION['stoken'] = false;
        echo json_encode(array('opcode' => '403'));
        exit;
      } else if (valtoken($db, session_id(), $ctoken, 'LPAGE', $secret, $logpath, 10) === true) {
        $ctoken = null;

        // Construct initial user table
        try {
          $db->exec('CREATE TABLE IF NOT EXISTS users (uid TEXT, uname TEXT, phash TEXT, flimit INTEGER)');
        } catch(PDOException $e) {
          $logmsg = ' ' . $e;
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
          $db = null;
          $sth = null;
          exit(json_encode(array('statcode' => 'Error', 'failmsg' => 'An internal server error has occured.')));
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
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
          $db = null;
          $sth = null;
          exit(json_encode(array('statcode' => 'Error', 'failmsg' => 'An internal server error has occured.')));
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
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
            $db = null;
            $sth = null;
            exit(json_encode(array('statcode' => 'Error', 'failmsg' => 'An internal server error has occured.')));
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
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
            $db = null;
            $sth = null;
            exit(json_encode(array('statcode' => 'Error', 'failmsg' => 'An internal server error has occured.')));
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
            @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
            $db = null;
            $sth = null;
            exit(json_encode(array('statcode' => 'Error', 'failmsg' => 'An internal server error has occured.')));
          }
        }

        // "Log In" Dialog Box
        if ($pu == true) {
          if (!empty($_POST['username']) && !empty($_POST['password']) == true) {
            if (!empty($_POST['token'])) {

              // Attempt to log a user in
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
                        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg4 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);

                        // ... or load the dialog box.
                      } else {
                        $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                        $token = newtoken(session_id(), 'LDATA', $secret);
                        echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'failmsg' => 'Error: You are not allowed to log in.'));
                        $token = null;
                        $logmsg .= ' LOGIN PAGE, AUTH FAILURE: User "' . $_POST['username'] . '" is not allowed to log in.';
                      }
                    } else {
                      $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                      $token = newtoken(session_id(), 'LDATA', $secret);
                      echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'failmsg' => 'FATAL DATABASE ERROR: Unable to log in.'));
                      $token = null;
                      $logmsg .= ' LOGIN PAGE, FATAL DATABASE ERROR: UID for User "' . $_POST['username'] . '" has been tampered with! You must delete the user.';
                    }
                  } else {
                    $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                    $token = newtoken(session_id(), 'LDATA', $secret);
                    echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'failmsg' => 'Error: You have entered an incorrect username or password.'));
                    $token = null;
                    $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Wrong password for user "' . $_POST['username'] . '".';
                  }
                } else {
                  $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                  $token = newtoken(session_id(), 'LDATA', $secret);
                  echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'failmsg' => 'Error: You have entered an incorrect username or password.'));
                  $token = null;
                  $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Username "' . $_POST['username'] . '" does not exist.';
                }
              } else if (valtoken($db, session_id(), $_POST['token'], 'LDATA', $secret, $logpath, 300) == false) {
                $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                $token = newtoken(session_id(), 'LDATA', $secret);
                echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'failmsg' => 'Error: Invalid/Expired Token.'));
                $token = null;
                $logmsg .= " LOGIN PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
              }
            } else {
              $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
              $logmsg .= " LOGIN PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
              @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
              $token = newtoken(session_id(), 'LDATA', $secret);
              echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'failmsg' => 'Error: Invalid/Expired Token.'));
              $token = null;
              exit;
            }
          } else if ($_SESSION['stoken'] === false) {
            $token = newtoken(session_id(), 'LDATA', $secret);
            echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'OK', 'token' => $token));
            $token = null;
            $logmsg .= " LOGIN PAGE, OK";
          }

          // "Create Primary Administrator" Dialog Box
        } else if ($pu == false) {
          if (!empty($_POST['puusername']) && !empty($_POST['pupassword'])) {
            if (!empty($_POST['putoken'])) {
              if (valtoken($db, session_id(), $_POST['putoken'], 'PUDATA', $secret, $logpath, 300) == true) {
                createpu($db, $_POST['puusername'], $_POST['pupassword'], $secret, $logpath);
                $token = newtoken(session_id(), 'LDATA', $secret);
                echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'OK', 'token' => $token, 'msg' => 'Primary Administrator Account Created!'));
                $token = null;
                $logmsg .= " LOGIN PAGE, OK: Primary User created!";
              } else if (valtoken($db, session_id(), $_POST['putoken'], 'PUDATA', $secret, $logpath, 300) == false) {
                $_SESSION['stoken'] == false;
                $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
                $token = newtoken(session_id(), 'LDATA', $secret);
                echo json_encode(array('opcode' => 'PUPage', 'statcode' => 'Error', 'token' => $token, 'failmsg' => 'Error: Invalid/Expired Token.'));
                $token = null;
              }
            } else {
              $_SESSION['stoken'] == false;
              $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
              @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
              $token = newtoken(session_id(), 'LDATA', $secret);
              echo json_encode(array('opcode' => 'PUPage', 'statcode' => 'Error', 'failmsg' => 'Error: Invalid/Expired Token.'));
              $token = null;
              exit;
            }
          } else {
            $putoken = newtoken(session_id(), 'PUDATA', $secret);
            echo json_encode(array('opcode' => 'PUPage', 'statcode' => 'OK', 'createputoken' => $putoken));
            $putoken = null;
            $logmsg .= " PRIMARY USER CREATION PAGE, OK";
          }
        }
      }
    } else {
      $logmsg .= " LOGIN PAGE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
      $_SESSION['stoken'] = false;
      echo json_encode(array('opcode' => 403));
      exit;
    }
  }
} else {
  echo json_encode(array('opcode' => 403));
  exit;
}
?>
