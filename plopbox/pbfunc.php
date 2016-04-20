<?php
// PlopBox FileBrowser Global Functions
if (isset($func)) {

  // Log write error
  function logerror() {
    echo 'ERROR writing PlopBox log! Check $logpath in pbconf.php';
    syslog(LOG_ERR, 'PlopBox: ERROR writing PlopBox log! Check $logpath in pbconf.php');
  }

  function boolinate($string) {
    return $string == 'false' ? false:true;
  }

  // Generate Token
  function newtoken($sid, $s) {
    $tkn = dechex(mt_rand());
    $time = dechex(time());
    return $tkn . '-' . $time . '-' . hash_hmac('sha1', $tkn . $time . $sid, $s);
  }

  // Generate Session Token
  function newstoken($sid, $uid, $r, $s) {
    $tkn = dechex(mt_rand());
    $time = dechex(time());
    return $tkn . '-' . $time . '-' . hash_hmac('sha1', $r . $uid . $tkn . $time . $sid, $s);
  }

  // Generate User ID
  function newuid($db, $u, $r, $a, $s) {
    $rand = mt_rand(1000000000, 9999999999);
    $uid = dechex($rand) . '-' . $r . '-' . hash_hmac('md5', $rand . $u . $r, $s);
    if ($a === true) {
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
    } else if ($a === false) {
      return $uid;
    }
  }

  // Validate Token
  function valtoken($sid, $t, $s, $to) {
    $tin = explode('-', $t);
    if (hash_hmac('sha1', $tin[0] . $tin[1] . $sid, $s) == $tin[2]) {
      if ($_SERVER['REQUEST_TIME'] - hexdec($tin[1]) > $to) {
        return false;
      } else if ($_SERVER['REQUEST_TIME'] - hexdec($tin[1]) < $to) {
        return true;
      }
    } else {
      return false;
    }
  }

  // Validate Session Token
  function valstoken($sid, $uid, $t, $s, $to) {
    if ($t === false) {
      return false;
    }
    $sin = explode('-', $t);
    if (hash_hmac('sha1', explode('-', $uid)[1] . $uid . $sin[0] . $sin[1] . $sid, $s) == $sin[2]) {
      if ($_SERVER['REQUEST_TIME'] - hexdec($sin[1]) > $to) {
        return false;
      } else if ($_SERVER['REQUEST_TIME'] - hexdec($sin[1]) < $to) {
        return true;
      }
    } else {
      return false;
    }
  }

  // Validate User ID
  function valuid($uid, $u, $rr, $s) {
    $idt = explode('-', $uid);
    $role = explode('/', $idt['1']);
    $reqrole = explode('/', $rr);
    if (hash_hmac('md5', hexdec($idt['0']) . $u . $idt['1'], $s) === $idt['2']) {
      for ($index = 0, $count = 5; $index < $count; $index++) {
      if ($reqrole[$index] !== '2') {
        if ($reqrole[$index] === $role[$index]) {
          $check = true;
        } else {
          return false;
        }
    }
  }
  } else {
    return false;
  }
  return $check;
  }

} else {
  header("HTTP/4.01 403 Forbidden");
}
?>
