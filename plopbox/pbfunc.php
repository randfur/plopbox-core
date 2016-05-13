<?php
// PlopBox FileBrowser Global Functions
if (isset($func)) {

  // Log write error
  function logerror() {
    echo 'ERROR writing PlopBox log! Check "logpath" in pbconf.ini';
    syslog(LOG_ERR, 'PlopBox: ERROR writing PlopBox log! Check "logpath" in pbconf.ini');
  }

  // Process Log-Out
  function logout($db, $logpath, $logmsg, $extra = null) {
    retire($db, 'token', $_SESSION['stoken'], $logpath);
    retire($db, 'uid', $_SESSION['uid'], $logpath);
    $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
    if ($extra !== null) {
      $logmsg .= $extra;
    } else {
      $logmsg .= ': User "' . $_SESSION['user'] . '" has logged out.';
    }
    @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
  }

  // Message Bar
  function msg($type, $nature = 'msg', $custom = '') {
    switch ($type) {
      case 'badtoken':
      $nature = 'failmsg';
      $msg = 'Error: Invalid/Expired Token.';
      break;
      case 'noperm_server':
      $nature = 'failmsg';
      $msg = 'Error: You do not have permission to change server settings.';
      break;
      case 'noperm_users':
      $nature = 'failmsg';
      $msg = 'Error: You do not have permission to manage users.';
      break;
      case 'noperm_settings':
      $nature = 'failmsg';
      $msg = 'Error: You do not have permission to change settings.';
      break;
      case 'badlogin':
      $nature = 'failmsg';
      $msg = 'Error: You have entered an incorrect username or password.';
      break;
      case 'custom':
      $nature = $nature;
      $msg = $custom;
      break;
    }
    return '<div style="visibility:visible;" id="msg" class="' . $nature . '">' . $msg . '<img alt="close" class="msgclose" onclick="msgclose()" src="/plopbox/images/controls/close.png"></div>';
  }

  // Simply converts string "false" to boolean false
  function boolinate($string) {
    return $string == 'false' ? false:true;
  }

  // usort() Functions
  function cdatedesc($a, $b) {
    return $a['date'] - $b['date'];
  }
  function cdateasc($a, $b) {
    return $b['date'] - $a['date'];
  }

  // Sort file array by date
  function sortfiles($sort, $files) {
    if ($sort === 2) {
      usort($files, 'cdatedesc');
    } else if ($sort === 3) {
      usort($files, 'cdateasc');
    }
    return $files;
  }

  // Remove expired token/uid hashes from the illegal lists
  // (1 in 5 chance)
  function gc($db, $logpath) {
    if (mt_rand(0, 5) === 3) {
      try {
        $sth = $db->prepare('DELETE FROM illegal_tokens WHERE born<:t');
        $sth->bindValue(':t', time(), PDO::PARAM_STR);
        $sth->execute();
        $sth = $db->prepare('DELETE FROM illegal_uids WHERE born<:t');
        $sth->bindValue(':t', time(), PDO::PARAM_STR);
        $sth->execute();
      } catch(PDOException $e) {
        $logmsg = ' ' . $e;
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        $db = null;
        $sth = null;
        exit($e);
      }
    }
  }

  // Add a token/userid hash to the illegal lists
  function retire($db, $type, $input, $logpath) {
    if ($type === 'token') {
      try {
        $sth = $db->prepare('INSERT INTO illegal_tokens (tokenhash, born) VALUES (:tokenhash, :born)');
        $sth->bindValue(':tokenhash', explode('-', $input)[2]);
        $sth->bindValue(':born', (int) explode('-', $input)[1]);
        $sth->execute();
        $sth = null;
      } catch(PDOException $e) {
        $logmsg = ' ' . $e;
        @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
        $db = null;
        $sth = null;
        exit($e);
      }
    } else if ($type === 'uid') {
      try {
        $sth = $db->prepare('INSERT INTO illegal_uids (uidhash, born) VALUES (:uidhash, :born)');
        $sth->bindValue(':uidhash', explode('-', $input)[3]);
        $sth->bindValue(':born', (int) explode('-', $input)[1]);
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
    return;
  }

  // Generate Token
  function newtoken($sid, $c, $s) {
    $tkn = dechex(mt_rand());
    $time = dechex(time());
    return $tkn . '-' . $time . '-' . hash_hmac('sha1', $tkn . $time . $sid . $c, $s);
  }

  // Generate Session Token
  function newstoken($sid, $uid, $s) {
    $tkn = dechex(mt_rand());
    $time = dechex(time());
    $role = explode('-', $uid)[2];
    return $tkn . '-' . $time . '-' . hash_hmac('sha1', $role . $uid . $tkn . $time . $sid, $s);
  }

  // Validate Token
  function valtoken($db, $sid, $t, $c, $s, $logpath, $to = false) {
    $tin = explode('-', $t);
    try {
      $sth = $db->prepare('SELECT tokenhash FROM illegal_tokens WHERE tokenhash=:t');
      $sth->bindValue(':t', $tin[2], PDO::PARAM_STR);
      $sth->execute();
      while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if (isset($row['token'])) {
          if ($row['token'] === $tin[2]) {
            $sth = null;
            return false;
          } else {
            continue;
          }
        }
      }
    } catch(PDOException $e) {
      $logmsg = ' ' . $e;
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
      $db = null;
      $sth = null;
      exit($e);
    }
    if (hash_hmac('sha1', $tin[0] . $tin[1] . $sid . $c, $s) == $tin[2]) {
      if ($to !== false) {
        if ($_SERVER['REQUEST_TIME'] - hexdec($tin[1]) > $to) {
          return false;
        } else if ($_SERVER['REQUEST_TIME'] - hexdec($tin[1]) < $to) {
          return true;
        }
      } else {
        return true;
      }
    } else {
      return false;
    }
  }

  // Validate Session Token
  function valstoken($db, $sid, $uid, $t, $s, $logpath, $to = false) {
    if ($t === false) {
      return false;
    }
    $sin = explode('-', $t);
    try {
      $sth = $db->prepare('SELECT tokenhash FROM illegal_tokens WHERE tokenhash=:t');
      $sth->bindValue(':t', $sin[2], PDO::PARAM_STR);
      $sth->execute();
      while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if (isset($row['token'])) {
          if ($row['token'] === $sin[2]) {
            $sth = null;
            return false;
          } else {
            continue;
          }
        }
      }
    } catch(PDOException $e) {
      $logmsg = ' ' . $e;
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
      $db = null;
      $sth = null;
      exit($e);
    }
    if (hash_hmac('sha1', explode('-', $uid)[2] . $uid . $sin[0] . $sin[1] . $sid, $s) == $sin[2]) {
      if ($to !== false) {
        if ($_SERVER['REQUEST_TIME'] - hexdec($sin[1]) > $to) {
          return false;
        } else if ($_SERVER['REQUEST_TIME'] - hexdec($sin[1]) < $to) {
          return true;
        }
      } else {
        return true;
      }
    } else {
      return false;
    }
  }

  // Validate User ID & Return Permissions
  function valuid($db, $uid, $u, $s, $logpath, $to = false) {
    $idt = explode('-', $uid);
    $role = explode('/', $idt[2]);
    $check = array();
    $count = 0;
    $timedout = false;
    try {
      $sth = $db->prepare('SELECT uidhash FROM illegal_uids WHERE uidhash=:uid');
      $sth->bindValue(':uid', $idt[3], PDO::PARAM_STR);
      $sth->execute();
      while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if (isset($row['uid'])) {
          if ($row['uid'] === $idt[3]) {
            $sth = null;
            return 'invalid';
          } else {
            continue;
          }
        }
      }
    } catch(PDOException $e) {
      $logmsg = ' ' . $e;
      @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
      $db = null;
      $sth = null;
      exit($e);
    }
    if (hash_hmac('md5', $idt['0'] . $idt['1'] . $u . $idt['2'], $s) == $idt['3']) {
      if ($to !== false) {
        if ($_SERVER['REQUEST_TIME'] - hexdec($idt[1]) > $to) {
          $timedout = true;
        }
      }
      if ($timedout === false) {
        foreach ($role as $flag) {
          if ($flag == '1') {
            $check[$count] = true;
            $count++;
          } else if ($flag == '0') {
            $check[$count] = false;
            $count++;
          }
        }
      } else if ($timedout === true) {
        return 'invalid';
      }
    } else {
      return 'invalid';
    }
    return $check;
  }

  // Generate new User ID from permission string
  function newuid($db, $u, $r, $s, $logpath, $update = false) {
    $rand = dechex(mt_rand(1000000000, 9999999999));
    $time = dechex(time());
    $uid = $rand . '-' . $time . '-' . $r . '-' . hash_hmac('md5', $rand . $time . $u . $r, $s);
    if ($update === true) {
      try {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sth = $db->prepare('UPDATE users SET uid=:uid WHERE uname=:u');
        $sth->bindValue(':uid', $uid);
        $sth->bindValue(':u', $u);
        $sth->execute();
        $sth = null;
        return $uid;
      }
      catch(PDOException $e) {
        $logmsg = ' ' . $e;
        @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
        $db = null;
        $sth = null;
        exit($e);
      }
    } else if ($update === false) {
      return $uid;
    }
  }

  // Generate new User ID from old User ID
  function recalcuid($db, $olduid, $u, $s, $logpath, $update = false) {
    $uidrole = explode('-', $olduid)['2'];
    $rand = dechex(mt_rand(1000000000, 9999999999));
    $time = dechex(time());
    if (valuid($db, $olduid, $u, $s, $logpath) !== 'invalid') {
      $newuid = $rand . '-' . $time . '-' . $uidrole . '-' . hash_hmac('md5', $rand . $time . $u . $uidrole, $s);
      if ($update === true) {
        try {
          $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $sth = $db->prepare('UPDATE users SET uid=:uid WHERE uname=:u');
          $sth->bindValue(':uid', $newuid);
          $sth->bindValue(':u', $u);
          $sth->execute();
          $sth = null;
          return $newuid;
        }
        catch(PDOException $e) {
          $logmsg = ' ' . $e;
          @file_put_contents($logpath . "pblog.txt", $logmsg . PHP_EOL, FILE_APPEND) or logerror();
          $db = null;
          $sth = null;
          exit($e);
        }
      } else if ($update === false) {
        return $newuid;
      }
    } else {
      return false;
    }
  }

} else {
  header("HTTP/4.01 403 Forbidden");
}
?>
