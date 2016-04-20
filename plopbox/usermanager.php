<?php
// PlopBox User Management Module
// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/usermanager.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    header("HTTP/4.01 403 Forbidden");
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken(session_id(), $ctoken, $secret, 10) == false) {
        $logmsg .= " USER MANAGER, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        if (session_status() == PHP_SESSION_ACTIVE) {
          $_SESSION['stoken'] = false;
        }
        header("HTTP/4.01 403 Forbidden");
        exit;
      } else if (valtoken(session_id(), $ctoken, $secret, 10) == true) {
        $ctoken = null;
        if (valstoken(session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, 1800) == false) {
          $logmsg .= " USER MANAGER, ACCESS DENIED: INVALID/EXPIRED SESSION TOKEN";
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          $_SESSION['stoken'] = false;
          header("HTTP/4.01 403 Forbidden");
          exit;
        } else {

          // Verify User can Manage users
          if (valuid($_SESSION['uid'], $_SESSION['user'], '1/1/1/2/2/1', $secret) === true) {
            echo "VALID. USER ID OK. PermVALID HashVALID (6int crosscheckok)";
          } else {
            echo "NOT VALID";
          }


        }
      } else {
        $logmsg .= " USER MANAGER, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        $_SESSION['stoken'] = false;
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
