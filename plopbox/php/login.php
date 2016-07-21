<?php
// PlopBox FileBrowser Login Module

// Check Core Mothership Token
if (function_exists('valtoken')) {
  if (!empty($ctoken)) {
    if (valtoken($db, $ctoken, 'LPAGE', $secret, $logpath, 10) === false) {
      $logmsg .= " LOGIN PAGE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
      $_SESSION['stoken'] = false;
      echo json_encode(array('opcode' => '403'));
      exit;
    } else if (valtoken($db, $ctoken, 'LPAGE', $secret, 10) === true) {
      $ctoken = null;

      // Verify Primary Administrator exists
      //TODO: This behavior is depreciated, clean it up
      $pu = true;

      // Create Primary User
      // TODO: This behavior is depreciated, clean it up
      function createpu($db, $username, $password, $secret) {
        $db->insert("users", [
            "uid" => newuid($db, $username, '1/1/1/1/1/1/1/1/1', $secret),
            "uname" => $username,
            "phash" => password_hash($password, PASSWORD_BCRYPT),
            "flimit" => 100]);
      }

      // Verify Username Exists
      function finduname($db, $username) {
        $result = $db->select("users",
            "username",
            ["username[=]" => $username]);
        if ($result === false) {
          return false;
        } else {
          return true;
        }
      }

      // Find Password Hash, Role, and Page Item Limit for given Username
      function fetchuserdata($db, $username) {
        return $db->select("users",
            "uid",
            "uname",
            "phash",
            "flimit",
            ["uname[=]" => $username]);
      }

      // "Log In" Dialog Box
      if ($pu == true) {
        if (!empty($_POST['username']) && !empty($_POST['password']) == true) {
          if (!empty($_POST['token'])) {

            // Attempt to log a user in
            if (valtoken($db, session_id(), $_POST['token'], 'LDATA', $secret, 300) == true) {
              if (finduname($db, $_POST['username'], $logpath) !== false) {
                $udata = fetchuserdata($db, $_POST['username'], $logpath);
                if (password_verify($_POST['password'], $udata['phash'])) {
                  $perm = valuid($db, $udata['uid'], $udata['uname'], $secret);
                  if ($perm["success"] === true) {
                    if ($perm["login"] === true) {

                      // Create new JWT Session Token
                      $uid = recalcuid($db, explode('-', $udata['uid'])[3], $udata['uname'], $secret, $logpath, true);
                      $perm = valuid($db, $uid, $uname, $secret, $logpath, 1800);
                      $jwtSigner = new Sha256();
                      $jwtKeychain = new Keychain();

                      $jwtToken = (new Builder())->setIssuer($host)
                      ->setAudience($_SERVER['REMOTE_ADDR'])
                      ->setId(dechex(mt_rand()))
                      ->setIssuedAt(time())
                      ->setNotBefore(time())
                      ->setExpiration(time() + 1800)
                      ->set('sidHash', newstoken(session_id(), $uid, $secret))
                      ->set('uidHash', explode('-', $uid)[3])
                      ->sign($signer, $keychain->getPrivateKey())
                      ->getToken();

                      // Send JWT Session Token
                      echo $jwtToken;

                      $logmsg4 .= ' LOGIN PAGE, AUTH OK: User "' . $_SESSION['user'] . '" logged in successfully.';
                      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg4 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);

                      // ... or load the dialog box.
                    } else {
                      $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                      $token = newtoken(session_id(), 'LDATA', $secret);
                      echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'msg' => 'Error: You are not allowed to log in.'));
                      $token = null;
                      $logmsg .= ' LOGIN PAGE, AUTH FAILURE: User "' . $_POST['username'] . '" is not allowed to log in.';
                    }
                  } else {
                    $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                    $token = newtoken(session_id(), 'LDATA', $secret);
                    echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'msg' => 'FATAL DATABASE ERROR: Unable to log in.'));
                    $token = null;
                    $logmsg .= ' LOGIN PAGE, FATAL DATABASE ERROR: UID for User "' . $_POST['username'] . '" has been tampered with! You must delete the user.';
                  }
                } else {
                  $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                  $token = newtoken(session_id(), 'LDATA', $secret);
                  echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'msg' => 'Error: You have entered an incorrect username or password.'));
                  $token = null;
                  $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Wrong password for user "' . $_POST['username'] . '".';
                }
              } else {
                $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
                $token = newtoken(session_id(), 'LDATA', $secret);
                echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'msg' => 'Error: You have entered an incorrect username or password.'));
                $token = null;
                $logmsg .= ' LOGIN PAGE, AUTH FAILURE: Username "' . $_POST['username'] . '" does not exist.';
              }
            } else if (valtoken($db, session_id(), $_POST['token'], 'LDATA', $secret, $logpath, 300) == false) {
              $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
              $token = newtoken(session_id(), 'LDATA', $secret);
              echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'msg' => 'Error: Invalid/Expired Token.'));
              $token = null;
              $logmsg .= " LOGIN PAGE, AUTH FAILURE: INVALID/EXPIRED TOKEN";
            }
          } else {
            $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
            $logmsg .= " LOGIN PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
            $token = newtoken(session_id(), 'LDATA', $secret);
            echo json_encode(array('opcode' => 'LoginPage', 'statcode' => 'Error', 'token' => $token, 'msg' => 'Error: Invalid/Expired Token.'));
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
              echo json_encode(array('opcode' => 'PUPage', 'statcode' => 'Error', 'token' => $token, 'msg' => 'Error: Invalid/Expired Token.'));
              $token = null;
            }
          } else {
            $_SESSION['stoken'] == false;
            $logmsg .= " PRIMARY USER CREATION PAGE, AUTH FAILURE: NO TOKEN (Suspicious!)";
            @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror(__LINE__);
            $token = newtoken(session_id(), 'LDATA', $secret);
            echo json_encode(array('opcode' => 'PUPage', 'statcode' => 'Error', 'msg' => 'Error: Invalid/Expired Token.'));
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
    echo json_encode(array('opcode' => 403));
    exit;
  }
} else {
  echo json_encode(array('opcode' => 403));
  exit;
}
?>
