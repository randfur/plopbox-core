<?php
// PlopBox FileBrowser Global Functions
if (isset($funcload)) {

  // Convert string "false" to boolean false
  function boolinate($string) {
    return $string == 'false' ? false:true;
  }

  // Log write error
  function logerror($lnum = 'undefined') {
    echo 'ERROR writing PlopBox log near line ' . $lnum . '! Check "logpath" in pbconf.ini';
    syslog(LOG_ERR, 'PlopBox: ERROR writing PlopBox log newr line ' . $lnum . '! Check "logpath" in pbconf.ini');
  }

  // Process Log-Out
  function logout($db, $logpath, $jwtToken, $sid, $username) {
    retire($db, explode($sid)[1], $sid);
    retire($db, $jwtToken->getClaim('iat'), $jwtToken);
    $logmsg .= ': User "' . $username . '" has logged out.';
    @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
  }

  function checkBlacklist ($input) {
    return $db->select("blacklist",
        "data",
        ["data[=]" => $input]);
  }

  // Add a Token to the blacklist
  function retire($db, $born, $input) {
    $db->insert("blacklist", [
      "data" => $input,
      "born" => $born
    ]);
  }

  // Generate Generic Token
  function newtoken($secret, $command = "") {
    $rand = dechex(mt_rand());
    $time = dechex(time());
    return $rand . '-' . $time . '-' . hash_hmac('sha256', $rand . $time . $command, $secret);
  }

  // Generate Session ID Token
  function newsid($uid, $secret) {
    $rand = dechex(mt_rand());
    $time = dechex(time());
    return $rand . '-' . $time . '-' . hash_hmac('sha256', $uid . $rand . $time, $secret);
  }

  // Validate Generic Token
  function valtoken($db, $token, $command, $secret, $to = false) {
    // Check blacklist for supplied Token
    $bToken = checkBlacklist($token);
    if ($bToken === $token) {
      return false;
    } else if ($bToken === false) {
      // Match hash signature
      $tokenArray = explode('-', $token);
      if (hash_hmac('sha256', $tokenArray[0] . $tokenArray[1] . $sid . $command, $secret) == $tokenArray[2]) {
        // Check if Token has expired
        if ($timeout !== false) {
          if ($_SERVER['REQUEST_TIME'] - hexdec($tokenArray[1]) > $timeout) {
            return false;
          } else if ($_SERVER['REQUEST_TIME'] - hexdec($tokenArray[1]) < $timeout) {
            return true;
          }
        } else {
          return true;
        }
      } else {
        return false;
      }
    }
  }

  // Validate Session ID Token & return boolean
  function valsid($db, $uid, $sid, $secret, $timeout = false) {
    // Check blacklist for supplied SID
    $bSid = checkBlacklist($sid);
    if ($bSid === $sid) {
      return false;
    } else if ($bSid === false) {
      // Match hash signature
      $sidArray = explode('-', $sid);
      if (hash_hmac('sha256', explode('-', $uid)[2] . $uid . $sidArray[0] . $sidArray[1], $secret) === $sidArray[2]) {
        // Check if SID has expired
        if ($timeout !== false) {
          if ($_SERVER['REQUEST_TIME'] - hexdec($sidArray[1]) > $timeout) {
            return false;
          } else if ($_SERVER['REQUEST_TIME'] - hexdec($sidArray[1]) < $timeout) {
            return true;
          }
        } else {
          return true;
        }
      } else {
        return false;
      }
    }
  }

  // Validate User ID Token & Return boolean Permissions Array or string "invalid"
  function valuid($db, $uid, $username, $secret, $timeout = false) {
    // Check blacklist for the supplied UID
    $bUid = checkBlacklist($uid);
    if ($bUid === $uid) {
      return ["success" => false];
    } else if ($bUid === false) {
      // Get equivalent UID from Database
      $dbUid = $db->select("users",
          "uid",
          ["uid[=]" => $uid]);
      // Check if Database UID matches provided UID
      if ($dbUid === false) {
        return ["success" => false];
      } else if ($dbUid === $uid) {
        // Match hash signature
        $uidArray = explode('-', $uid);
        if (hash_hmac('sha256', $uidArray[0] . $uidArray[1] . $username . $uidArray[2], $secret) === $uidArray[3]) {
          // Check if UID has expired
          if ($timeout !== false) {
            if ($_SERVER['REQUEST_TIME'] - hexdec($uidArray[1]) > $timeout) {
              return ["success" => false];
            }
          }
          // Construct boolean permission array
          $permFlags = explode('/', explode('-', $dbUid)[2]);
          $i = 0;
          foreach ($permFlags as $flag) {
            if ($flag == '1') {
              $permArray[] = true;
            } else if ($flag == '0') {
              $permArray[] = false;
            }
          }
          // Label boolean permission array
          $permArrayNamed = array(
            "login" => $perms[0],
            "view" => $perms[1],
            "mod" => $perms[2],
            "newDir" => $perms[3],
            "download" => $perms[4],
            "upload" => $perms[5],
            "delete" => $perms[6],
            "admin" => $perms[7],
            "superadmin" => $perms[8],
            "success" => true
          );
          return $permArrayNamed;
        } else {
          return ["success" => false];
        }
      }
    }
  }

  // Generate new User ID Token from permissions a string
  function newuid($db, $username, $permissions, $secret) {
    // Generate UID
    $rand = dechex(mt_rand());
    $time = dechex(time());
    $uid = $rand . '-' . $time . '-' . $permissions . '-' . hash_hmac('sha256', $rand . $time . $username . $permissions, $secret);
    // Update user in DB
    $db->update("users",
        "uid",
        ["uname[=]" => $uid]);
  }

  // Generate a new User ID Token from an old User ID
  function recalcuid($db, $oldUid, $username, $secret) {
    // Validate old UID
    if (valuid($db, $oldUid, $username, $secret) !== 'invalid') {
      // Generate new UID
      $permissions = explode('-', $oldUid)['2'];
      $rand = dechex(mt_rand());
      $time = dechex(time());
      $uid = $rand . '-' . $time . '-' . $permissions . '-' . hash_hmac('sha256', $rand . $time . $username . $permissions, $secret);
      // Update user in DB
      $db->update("users",
          "uid",
          ["uid[=]" => $oldUid]);
    } else {
      return false;
    }
  }

} else {
  header("HTTP/4.01 403 Forbidden");
}
?>
