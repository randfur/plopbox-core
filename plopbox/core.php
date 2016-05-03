<?php
// PlopBox Filebrowser Index Core

// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/core.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    header("HTTP/4.01 403 Forbidden");
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken($db, session_id(), $ctoken, 'CORE', $secret, $logpath, 10) === false) {
        $logmsg .= " INDEX CORE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        header("HTTP/4.01 403 Forbidden");
        exit;
      } else if (valtoken($db, session_id(), $ctoken, 'CORE', $secret, $logpath, 10) === true) {
        $ctoken = null;
        if (valstoken($db, session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, $logpath, 1800) === false) {
          $logmsg .= " INDEX CORE, ACCESS DENIED: INVALID/EXPIRED SESSION TOKEN";
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          retire($db, 'token', $_SESSION['stoken'], $logpath);
          retire($db, 'uid', $_SESSION['uid'], $logpath);
          $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
          header("HTTP/4.01 403 Forbidden");
          exit;
        } else {

          if ($perm[0] && $perm[1] === true) {
            // Scan the directory specified in the URI
            $dcont = scandir(($droot . $interlink), $sort);
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
                $ftarget = $droot . '/' . $interlink . $file;
                if (is_dir($ftarget)) {
                  ++$dcount;
                  $ddcont[$dkey]['item'] = $file;
                  $ddcont[$dkey]['date'] = filemtime($ftarget);
                  $dkey++;
                } else {
                  $fdcont[$fkey]['item'] = $file;
                  $fdcont[$fkey]['date'] = filemtime($ftarget);
                  $fkey++;
                }
              }
            }

            // Sort files & directories by date
            if (isset($_GET['sort'])) {
              if ($_GET['sort'] == 2 or 3) {
                $fdcont = sortfiles($dsort, $fdcont);
                $ddcont = sortfiles($dsort, $ddcont);
              }
            }

            // Merge file & directory arrays
            $entries = array_merge($ddcont, $fdcont);

            // Generate page navigation buttons
            if ($itemcount > $_SESSION['flimit']) {
              if ($fstart > 0) {
                if ($simplemode == false) {
                  $paginator = '<div class="paginatorbutton"><a class="paginatortext" href="' . $host . $interlink . '?start=' . ($fstart - $_SESSION['flimit']) . $smlink . $sortdatelink . '">⇦ Prev. Page </a></div>';
                } else if ($simplemode == true) {
                  $pagebuttons++;
                  $paginator = '<a href="' . $host . $interlink . '?start=' . ($fstart - $_SESSION['flimit']) . '&amp;simple=true' . $sortdatelink . '">⇦ Prev. Page </a>';
                }
              }
              if ($itemcount + $_SESSION['flimit'] > $fstart) {
                if ($fstart + $_SESSION['flimit'] <= $itemcount) {
                  if ($simplemode == false) {
                    $paginator .= '<div class="paginatorbutton"><a class="paginatortext" href="' . $host . $interlink . '?start=' . ($fstart + $_SESSION['flimit']) . $smlink . $sortdatelink . '"> Next Page ⇨</a></div>';
                  } else if ($simplemode == true) {
                    if ($pagebuttons == 1) {
                      $pbuttonsep = ' | ';
                    }
                    $paginator .= '<a href="' . $host . $interlink . '?start=' . ($fstart + $_SESSION['flimit']) . '&amp;simple=true' . $pbuttonsep . $sortdatelink . '"> Next Page ⇨</a>';
                  }
                }
              }
            }

            // Generate Navbar Array
            $uriarray = explode('/', rtrim($interlink, '/'));
            $navcount = 1;
            $lastnav = '';
            $navlinks = '';
            $navitem = array();
            unset($uriarray[0]);
            $uriarray = array_values($uriarray);
            foreach ($uriarray as $uriitem) {
              if ($simplemode == false) {
                $navitem[] = '<a href="' . $host . '/' . $lastnav .  $uriitem . '">' . $uriitem . '</a>';
                $lastnav .= $uriitem . '/';
                ++$navcount;
              } else if ($simplemode == true) {
                $navitem[] = '<a href="' . $host . '/' . $lastnav .  $uriitem . '/?simple=true">' . $uriitem . '</a>';
                $lastnav .= $uriitem . '/';
                ++$navcount;
              }
            }
          }
          foreach ($navitem as $naventry) {
            $navlinks .= $naventry . '/';
          }

          // Check if user is allowed to view files
          if ($perm[0] && $perm[1] === false) {
            if ($simplemode == false) {
              $output = '<div class="dirempty">You are not allowed<br>to view files.</div>';
            } else if ($simplemode == true) {
              $output = '<h1>You are not allowed to view files.</h1>';
            }
            $logmsg .= ' INDEX CORE, AUTH FAILURE: User "' . $_SESSION['user'] . '" is not allowed to view files.';
          } else if ($perm[0] && $perm[1] === true) {

            // Check if directory is empty
            if (!isset($dcont[0])) {
              if ($simplemode == false) {
                $output = '<div class="dirempty">Directory Empty</div>';
              } else if ($simplemode == true) {
                $output = '<h1>Directory Empty</h1>';
              }
              $logmsg .= 'INDEX CORE, OK: EMPTY ';
            }

            // Process File Entries
            if (isset($entries[0])) {
              $fkey = 0;
              foreach (array_slice($entries, $fstart, $_SESSION['flimit']) as $file) {
                // Define the target file/folder
                $ftarget = $droot . '/' . $interlink . $file['item'];
                $link = $file['item'];
                if (!is_dir($ftarget)) {
                  // Process Files
                  // Assign icon based on MIME type
                  $mime = strstr(finfo_file(finfo_open(FILEINFO_MIME), $ftarget ), ';', true);
                  $mime = str_replace('/', '-', $mime);
                  $mimed = "";
                  if ($mimedebug === 1) {
                    $mimed = ' - ' . $mime;
                  }
                  if (in_array($mime, $mimetypes)) {
                    $ficon =  $host . '/plopbox/images/mimetypes/' . $mime . '.png';
                  } else {
                    $ficon = $host . '/plopbox/images/mimetypes/application-x-zerosize.png';
                  }
                  // Calculate filesize
                  $fsize = filesize($ftarget);
                  $dec =  2;
                  $csize = 'BKMGTP';
                  $sizefactor = floor((strlen($fsize) - 1) / 3);
                  $fsize = sprintf("%.{$dec}f", $fsize / pow(1024, $sizefactor)) . ' <div class="sizefactor">' . @$csize[$sizefactor] . '</div>';
                  $files[$fkey]['date'] = $file['date'];
                  // Format file entry
                  if ($simplemode == true){
                    $fentries[$fkey]['item'] = '<tr><td><a href="' . htmlentities($link) . '"><img alt="' . $file['item'] . '" src="' . $ficon . '"></a></td>
                    <td class="indexcolname"><a href="' . htmlentities($link) . '">' . htmlentities($file['item']) . ' ' . $mimed . '</a></td>
                    <td class="indexcollastmod">' . date( "M j, Y - g:iA", $file['date']) . '</td>
                    <td class="indexcolsize">' . $fsize . '</td></tr>';
                  } else if ($simplemode == false) {
                    $fentries[$fkey]['item'] = '<div class="entry"><a href="' . rawurlencode($link) . '"><span title="' . htmlentities($file['item']) . '" class="entrylink"></span></a>
                    <div class="icon"><img alt="' . $file['item'] . '" src="' . $ficon . '"></div>
                    <div class="name">' . htmlentities($file['item']) . ' ' . $mimed . '</div>
                    <div class="mtime">' . date( "M j, Y - g:iA", $file['date']) . '</div>
                    <div class="size">' . $fsize . '</div></div>';
                  }
                } else if (is_dir($ftarget)) {
                  // Process Directories
                  $fsize = ' ';
                  $ficon = $host . '/plopbox/images/directory/folder.png';
                  $file[$fkey]['date'] = $file['date'];
                  // Inherit Simplemode URI arument in link
                  if (isset($_GET['simple'])) {
                    if ($simplemode == true) {
                      $link = $file['item'] . '/?simple=true';
                    } else if ($simplemode == false) {
                      $link = $file['item'] . '/?simple=false';
                    }
                  }
                  // Format directory entry
                  if ($simplemode == true) {
                    $fentries[$fkey]['item'] = '<tr><td><a href="' . rawurlencode($link) . '/?simple=true"><img alt="' . $file['item'] . '" src="' . $ficon . '"></a></td>
                    <td class="indexcolname"><a href="' . htmlentities($link) . '/?simple=true">' . htmlentities($file['item']) . '</a></td>
                    <td class="indexcollastmod">' . date( "M j, Y - g:iA", $file['date']) . '</td>
                    <td class="indexcolsize">' . $fsize . '</td></tr>';
                  } else if ($simplemode == false) {
                    $fentries[$fkey]['item'] = '<div class="entry"><a href="' . rawurlencode($link) . '"><span title="' . htmlentities($file['item']) . '" class="entrylink"></span></a>
                    <div class="icon"><img alt="' . $file['item'] . '" src="' . $ficon . '"></div>
                    <div class="name">' . htmlentities($file['item']) . '</div>
                    <div class="mtime">' . date( "M j, Y - g:iA", $file['date']) . '</div>
                    <div class="size">' . $fsize . '</div></div>';
                  }
                }
                $fkey++;
              }
              // Output file index arrays
              foreach ($fentries as $entry) {
                $output .= $entry['item'];
              }
              $logmsg .= ' INDEX CORE, OK: LISTING ' . $itemcount . ' ITEMS';
            }
          }
          require "plopbox/templates/main.php";
        }
      } else {
        $logmsg .= " INDEX CORE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        retire($db, 'token', $_SESSION['stoken'], $logpath);
        retire($db, 'uid', $_SESSION['uid'], $logpath);
        $_SESSION['stoken'] = $_SESSION['uid'] = $_SESSION['user'] = false;
        header("HTTP/4.01 403 Forbidden");
        exit;
      }
    }
  }
} else {
  header("HTTP/4.01 403 Forbidden");
}
?>
