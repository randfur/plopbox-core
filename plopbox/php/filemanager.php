<?php
// PlopBox File Manager Core

// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/php/filemanager.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    header("HTTP/1.0 403 Forbidden");
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken($db, session_id(), $ctoken, 'FMANAGER', $secret, $logpath, 10) == false) {
        $logmsg .= " FILE MANAGER, ACCESS DENIED: MISSING/INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        logout($db, $logpath, $logmsg);
        header("HTTP/1.0 403 Forbidden");
        exit;
      } else if (valtoken($db, session_id(), $ctoken, 'FMANAGER', $secret, $logpath, 10) == true) {
        $ctoken = null;
        if (valstoken($db, session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, $logpath, 1800) == false) {
          $logmsg .= " FILE MANAGER, ACCESS DENIED: INVALID/EXPIRED SESSION TOKEN";
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          logout($db, $logpath, $logmsg, ': Session Token for User "' . $_SESSION['user'] . '" is invalid or expired. Logging user out.');
          header("HTTP/1.0 403 Forbidden");
          exit;
        } else {

          // Upload a File
          function uploadfile($f, $droot, $interlink, $folderexclude) {
            $statcode = 1;
            $status = "";
            if (!empty($f)) {
              if (preg_match($folderexclude, $interlink) === 1) {
                return false;
              } else {
                $target_file = $droot . '/' . $interlink . basename($f);
                if (file_exists($target_file)) {
                  $status .= " The file already exists.";
                  $statcode = 0;
                }
                // Check file size
                if ($_FILES["fileToUpload"]["size"] > 5368709120) {
                  $status .= " Your file is too large.";
                  $statcode = 0;
                }
                // Check if $statcode is set to 0 by an error
                if ($statcode == 0) {
                  $status .= " Your file was not uploaded.";
                  // if everything is ok, try to upload file
                } else {
                  if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                    $status .= " The file ". basename($f). " has been uploaded.";
                  } else {
                    $status .= " There was an error uploading your file.";
                  }
                }
              }
            } else {
              $status .= " A file was not selected.";
            }
            return $status;
          }

          // Move a file or folder to the trash
          function trashfile($f, $droot, $interlink, $folderexclude) {
            if (preg_match($folderexclude, $interlink) === 1) {
              return false;
            }
            $target_files = array();
            foreach ($f as $file) {
              $target_files[] = $droot . '/' . $interlink . $file;
            }
            foreach ($target_files as $file) {
              echo ($file . '<br>');
            }
          }

          // Create a new folder
          function newfolder($f, $droot, $interlink, $folderexclude) {
            $status = "";
            if (preg_match($folderexclude, $interlink) === 1) {
              return false;
            }
            $folder_dest = $droot . '/' . $interlink . $f;
            $statcode = 1;
            if (ctype_alnum($f)) {
              if (file_exists($folder_dest)) {
                $status .= " Folder or file already exists. ";
                $statcode = 0;
              }
              // Check if $uploadOk is set to 0 by an error
              if ($statcode == 0) {
                $status .= " Your folder was not created. ";
                // if everything is ok, try to make folder
              } else {
                if (mkdir($folder_dest)) {
                  $status .= " The folder ". basename($folder_dest). " has been created.";
                } else {
                  $status .=  " There was an error creating the folder.";
                }
              }
            } else {
              $status .= " Only letters A-Z and numbers will be accepted. Your folder was not created.";
            }
            return $status;
          }

          // Process file operation commands
          if (valtoken($db, session_id(), $_POST['ftoken'], 'FUPLOAD', $secret, $logpath, 800)) {
            if ($perm[5] === true) {
              if (!empty($_FILES["fileToUpload"]["name"])) {
                $opresult = uploadfile($_FILES["fileToUpload"]["name"], $droot, $interlink, $folderexclude);
              } else {
                $opresult = "Error: No File";
                $logmsg .= ' FILE OPERATION FAILURE, UPLOAD: User "' . $_SESSION['user'] . '" did not select a file.';
              }
            } else {
              $opresult = "Error: You are not allowed to upload files.";
              $logmsg .= ' FILE OPERATION FAILURE, UPLOAD: User "' . $_SESSION['user'] . '" is not allowed to upload files.';
            }
          } else if (valtoken($db, session_id(), $_POST['ftoken'], 'NEWFOLDER', $secret, $logpath, 800)) {
            if ($perm[3] === true) {
              if (!empty($_POST["foldername"])) {
                $opresult = newfolder($_POST["foldername"], $droot, $interlink, $folderexclude);
              } else {
                $opresult = "Error: No folder name entered.";
                $logmsg .= ' FILE OPERATION FAILURE, NEWFOLDER: User "' . $_SESSION['user'] . '" did not enter a folder name.';
              }
            } else {
              $opresult = "Error: You are not allowed to create folders.";
              $logmsg .= ' FILE OPERATION FAILURE, NEWFOLDER: User "' . $_SESSION['user'] . '" is not allowed to create folders.';
            }
          } else if (valtoken($db, session_id(), $_POST['ftoken'], 'FDELETE', $secret, $logpath, 800)) {
            if ($perm[6] === true) {
              if (!empty($_POST["filestotrash"])) {
                $opresult = trashfile($_POST["filestotrash"], $droot, $interlink, $folderexclude);
              } else {
                $opresult = "Error: No File";
                $logmsg .= ' FILE OPERATION FAILURE, DELETE: User "' . $_SESSION['user'] . '" did not select a file.';
              }
            } else {
              $opresult = "Error: You are not allowed to delete files.";
              $logmsg .= ' FILE OPERATION FAILURE, DELETE: User "' . $_SESSION['user'] . '" is not allowed to delete files.';
            }
          }

        }
      } else {
        $logmsg .= " FILE MANAGER, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        retire($db, 'token', $_SESSION['stoken'], $logpath);
        retire($db, 'uid', $_SESSION['uid'], $logpath);
        $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
        header("HTTP/1.0 403 Forbidden");
        exit;
      }
    }
  }
} else {
  header("HTTP/4.01 403 Forbidden");
  exit;
}
?>
