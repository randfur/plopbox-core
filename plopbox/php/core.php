<?php
// PlopBox Filebrowser File Index

// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/php/core.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(array('opcode' => '403'));
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken($db, session_id(), $ctoken, 'CORE', $secret, $logpath, 10) === false) {
        $logmsg .= " INDEX CORE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        logout($db, $logpath, $logmsg);
        echo json_encode(array('opcode' => '403'));
        exit;
      } else if (valtoken($db, session_id(), $ctoken, 'CORE', $secret, $logpath, 10) === true) {
        $ctoken = null;
        if (valstoken($db, session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, $logpath, 1800) === false) {
          $logmsg .= " INDEX CORE, ACCESS DENIED: INVALID/EXPIRED SESSION TOKEN";
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          logout($db, $logpath, $logmsg, ': Session Token for User "' . $_SESSION['user'] . '" is invalid or expired. Logging user out.');
          echo json_encode(array('opcode' => '403'));
          exit;
        } else {

          if ($perm[0] && $perm[1] === true) {
            // Verify the requested directory exists
            if (!file_exists($droot . $interlink)) {
              echo json_encode(array('opcode' => '404'));
              exit;
            } else {

              // Initialize file/folder sorting scheme
              switch ($pbconf['index_options']['sortby']) {
                case "name":
                switch ($pbconf['index_options']['direction']) {
                  case "ascending":
                  $sortscheme = 0;
                  break;
                  case "descending":
                  $sortscheme = 1;
                  break;
                }
                break;
                case "date":
                switch ($pbconf['index_options']['direction']) {
                  case "ascending":
                  $sortscheme = 2;
                  break;
                  case "descending":
                  $$sortscheme = 3;
                  break;
                }
                break;
                case "size":
                switch ($pbconf['index_options']['direction']) {
                  case 'ascending':
                  $sortscheme = 4;
                  break;
                  case 'descending':
                  $sortscheme = 5;
                  break;
                }
                break;
              }
              if (isset($_GET['sort'])) {
                if ($_GET['sort'] == '0' or '1' or '2' or '3' or '4' or '5') {
                  $sortscheme = $_GET['sort'];
                  $sortconst = SCANDIR_SORT_ASCENDING;
                } else {
                  $sortscheme = 0;
                }
              }
              switch ($sortscheme) {
                case 0:
                $dsort = false;
                $sortconst = SCANDIR_SORT_ASCENDING;
                break;
                case 1:
                $dsort = false;
                $sortconst = SCANDIR_SORT_DESCENDING;
                break;
                case 2:
                $dsort = 2;
                break;
                case 3:
                $dsort = 3;
                break;
                case 4:
                $dsort = 4;
                break;
                case 5:
                $dsort = 5;
                break;
              }

              // Scan the directory specified in the URL
              $dcont = scandir(($droot . $interlink), $sortconst);
            }
            // Remove excluded items from directory file array
            foreach ($dcont as $file) {
              if (preg_match($fileexclude, $file) == 1) {
                unset($dcont[(array_search($file, $dcont))]);
              }
            }
            $dcont = array_values($dcont);
            $itemcount = count($dcont);

            // Populate item arrays
            foreach ($dcont as $file) {
              if (preg_match($fileexclude, $file) === 1) {
                continue;
              } else {
                $ftarget = $droot . '/' . $interlink . '/' . $file;
                if (is_dir($ftarget)) {
                  ++$dcount;
                  $ddcont[$dkey]['item'] = $file;
                  $ddcont[$dkey]['date'] = filemtime($ftarget);
                  $dkey++;
                } else {
                  $fdcont[$fkey]['item'] = $file;
                  $fdcont[$fkey]['date'] = filemtime($ftarget);
                  $fdcont[$fkey]['size'] = filesize($ftarget);
                  $fkey++;
                }
              }
            }

            // usort() File Sorting Functions
            // Date
            function cdatedesc($a, $b) {
              return $a['date'] - $b['date'];
            }
            function cdateasc($a, $b) {
              return $b['date'] - $a['date'];
            }
            // Size
            function csizedesc($a, $b) {
              return $a['size'] - $b['size'];
            }
            function csizeasc($a, $b) {
              return $b['size'] - $a['size'];
            }
            // Sort file array by date
            function dsortfiles($sort, $files, $dsort) {
              if ($dsort === 2) {
                usort($files, 'cdatedesc');
              } else if ($dsort === 3) {
                usort($files, 'cdateasc');
              }
              return $files;
            }
            // Sort file array by size
            function ssortfiles($sort, $files, $dsort) {
              if ($dsort === 4) {
                usort($files, 'csizedesc');
              } else if ($dsort === 5) {
                usort($files, 'csizeasc');
              }
              return $files;
            }

            // Sort files & directories by date
            if ($dsort === 2 or 3) {
              $fdcont = dsortfiles($dsort, $fdcont, $dsort);
              $ddcont = dsortfiles($dsort, $ddcont, $dsort);
            }
            // Sort only files by size
            if ($dsort === 4 or 5) {
              $fdcont = ssortfiles($dsort, $fdcont, $dsort);
            }
          }

          // Merge file & directory arrays
          $entries = array_merge($ddcont, $fdcont);

          // Check if user is allowed to view files
          if ($perm[0] && $perm[1] === false) {
            echo json_encode(array('opcode' => 'FileIndex', 'statcode' => 'ViewPermFail'));
            $logmsg .= ' INDEX CORE, AUTH FAILURE: User "' . $_SESSION['user'] . '" is not allowed to view files.';
          } else if ($perm[0] && $perm[1] === true) {

            // Check if directory is empty
            if (!isset($dcont[0])) {
              echo json_encode(array('opcode' => 'FileIndex', 'statcode' => 'Empty'));
              $logmsg .= 'INDEX CORE, OK: EMPTY ';
            }

            // Process File Entries
            if (isset($entries[0])) {
              $fkey = 0;
              foreach (array_slice($entries, $fstart, $_SESSION['flimit']) as $file) {
                // Define the target file/folder
                $ftarget = $droot . '/' . $interlink . '/' . $file['item'];
                $link = $fullurl . '/' . urlencode($file['item']);
                if (!is_dir($ftarget)) {
                  // Process Files
                  // Assign icon based on MIME type
                  $mime = strstr(finfo_file(finfo_open(FILEINFO_MIME), $ftarget ), ';', true);
                  $mime = str_replace('/', '-', $mime);
                  $mimed = "";
                  if ($mimedebug === true) {
                    $mimed = ' - ' . $mime;
                  }
                  if (in_array($mime, $mimetypes)) {
                    $ficon =  '/plopbox/images/mimetypes/' . $mime . '.png';
                  } else if (array_key_exists($mime, $mimetypes)) {
                    $ficon = '/plopbox/images/mimetypes/' . $mimetypes[$mime] . '.png';
                  } else {
                    $ficon = '/plopbox/images/mimetypes/unknown.png';
                  }
                  // Calculate filesize
                  $fsize = $file['size'];
                  $dec =  2;
                  $csize = 'BKMGTP';
                  $sizefactor = floor((strlen($fsize) - 1) / 3);
                  $fsize = sprintf("%.{$dec}f", $fsize / pow(1024, $sizefactor)) . ' <div class="sizefactor">' . @$csize[$sizefactor] . '</div>';
                  // Format file entry
                  $fileoutput[$fkey]['item'] = array("name" => $file['item'],
                  "icon" => $ficon,
                  "path" => $link,
                  "mtime" => date("M j, Y - g:iA", $file['date']),
                  "mtimeux" => $files[$fkey]['date'],
                  "fsize" => $fsize,
                  "fsizefactor" => @$csize[$sizefactor],
                  "mimetype" => $mimed,
                  "dir" => False);

                } else if (is_dir($ftarget)) {
                  // Process Directories
                  // Format directory entry
                  $DirOutput[$fkey]['item'] = array("name" => $file['item'],
                  "icon" => '/plopbox/images/directory/folder.png',
                  "path" => $link,
                  "mtime" => date("M j, Y - g:iA", $file['date']),
                  "mtimeux" => $files[$fkey]['date'],
                  "mimetype" => $mimed,
                  "dir" => True);
                }
                $fkey++;
              }
              // Output file index arrays
              foreach ($DirOutput as $entry) {
                $output[] = $entry['item'];
              }
              foreach ($FileOutput as $entry) {
                $output[] = $entry['item'];
              }
              echo json_encode(array("opcode" => "FileIndex", "statcode" => "00", "sort" => $sortscheme, "itemcount" => $itemcount, "flimit" => $_SESSION['flimit'], "fstart" => $fstart, "filedata" => $output));
              $logmsg .= ' INDEX CORE, OK: LISTING ' . $itemcount . ' ITEMS';
            }
          }
        }
      } else {
        $logmsg .= " INDEX CORE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        logout($db, $logpath, $logmsg);
        echo json_encode(array('opcode' => '403'));
        exit;
      }
    }
  }
} else {
  echo json_encode(array('opcode' => '403'));
  exit;
}
?>
