<?php
// PlopBox FileBrowser Functions

// Log write error
function logerror() {
  echo 'ERROR writing PlopBox log! Check $logpath in pbconf.php';
  syslog(LOG_ERR, 'PlopBox: ERROR writing PlopBox log! Check $logpath in pbconf.php');
}

// Generate Token
function newtoken($s) {
  $tkn = dechex(mt_rand());
  $time = dechex(time());
  $sid = dechex(session_id());
  return $tkn . '-' . $sid . '-' . $time . '-' . hash_hmac('sha1', $tkn . $sid . $time, $s);
}

// Validate Token
function valtoken($t, $s, $to) {
  if (empty($t)) {
    return false;
  }
  $tin = explode('-', $t);
  if (hash_hmac('sha1', $tin[0] . $tin[1] . $tin[2], $s) == $tin[3]) {
    if (hexdec($tin[1]) == session_id()) {
      if ($_SERVER['REQUEST_TIME'] - hexdec($tin[2]) > $to) {
        return false;
      } else if ($_SERVER['REQUEST_TIME'] - hexdec($tin[2]) < $to) {
        return true;
      }
    } else {
      return false;
    }
  } else {
    return false;
  }
}
?>
