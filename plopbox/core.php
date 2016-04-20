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
      if (valtoken(session_id(), $ctoken, $secret, 10) == false) {
        $logmsg .= " INDEX CORE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        if (session_status() == PHP_SESSION_ACTIVE) {
          $_SESSION['stoken'] = false;
        }
        header("HTTP/4.01 403 Forbidden");
        exit;
      } else if (valtoken(session_id(), $ctoken, $secret, 10) == true) {
        $ctoken = null;
        if (valstoken(session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, 1800) == false) {
          $logmsg .= " INDEX CORE, ACCESS DENIED: INVALID/EXPIRED SESSION TOKEN";
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          $_SESSION['stoken'] = false;
          header("HTTP/4.01 403 Forbidden");
          exit;
        } else {

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

          // Generate page navigation buttons
          if ($itemcount > $_SESSION['flimit']) {
            if ($fstart > 0) {
              if ($simplemode == false) {
                $paginator = '<div class="paginatorbutton"><a class="paginatortext" href="' . $host . $interlink . '?start=' . ($fstart - $_SESSION['flimit']) . $smlink . '">⇦ Prev. Page </a></div>';
              } else if ($simplemode == true) {
                $pagebuttons++;
                $paginator = '<a href="' . $host . $interlink . '?start=' . ($fstart - $_SESSION['flimit']) . '&amp;simple=true">⇦ Prev. Page </a>';
              }
            }
            if ($itemcount + $_SESSION['flimit'] > $fstart) {
              if ($fstart + $_SESSION['flimit'] <= $itemcount) {
                if ($simplemode == false) {
                  $paginator .= '<div class="paginatorbutton"><a class="paginatortext" href="' . $host . $interlink . '?start=' . ($fstart + $_SESSION['flimit']) . $smlink . '"> Next Page ⇨</a></div>';
                } else if ($simplemode == true) {
                  if ($pagebuttons == 1) {
                    $pbuttonsep = ' | ';
                  }
                  $paginator .= '<a href="' . $host . $interlink . '?start=' . ($fstart + $_SESSION['flimit']) . '&amp;simple=true">' . $pbuttonsep . ' Next Page ⇨</a>';
                }
              }
            }
          }

          // Generate Navbar Array
          $uriarray = explode('/', rtrim($interlink, '/'));
          $navcount = 1;
          $lastnav = '';
          $navitem[] = '';
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

          // Load stylesheet/favicon, header, & file manager dialog boxes
          if ($simplemode == true) {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>PlopBox Index - Browsing ' . $interlink . '</title>
            <link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon"></head>';
            echo '<a href="?logout=true&amp;simple=true">Log Out</a><br>';
            echo 'Browsing ';
            foreach ($navitem as $naventry) {
              echo $naventry . '/';
            }
            echo '<br>' . $paginator;
            echo '<br><a href="../?simple=true">Go Up a Directory</a>';
            echo '<table border="1"><tr><td> </td><td><b><a href="' . $interlink . '?sort=' . ($sortval ^ 1) . '&amp;simple=true">Name</a></b></td><td><b>Last Modified</b></td><td><b>Size</b></td></tr>';
          } else if ($simplemode == false){
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>PlopBox Index - Browsing ' . $interlink . '</title>
            <link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon">
            <meta name="viewport" content="width=500, minimum-scale=0.5, maximum-scale=1.0, user-scalable=yes">';
            echo '<link rel="stylesheet" type="text/css" href="' . $host . '/plopbox/style.css"></head>';
            include '/plopbox/header.html';
            if (!empty($opresult)) {
              echo '<div style="visibility:visible;" id="msg" class="msg">' . $opresult . '<img alt="close" class="msgclose" onclick="msgclose()" src="/plopbox/images/controls/close.png"></div>';
            }

            // "Upload File" Dialog Box
            $ftoken = newtoken(session_id(), $secret);
            echo '<div align="Center" id="uploadbox" style="visibility:hidden;" class="dialogbox">
            <div class="titlebar">Upload File<img alt="close" class="fileclose" onclick="uploadclose()" src="/plopbox/images/controls/close.png"></div>
            <div class="boxwrapper">
            <form action="' . $fullurl . '?fileop=1" method="post" enctype="multipart/form-data">
            Select file to upload:<br>
            <input type="hidden" name="ftoken" value="' . $ftoken . '">
            <input type="file" name="fileToUpload" id="fileToUpload"><br>
            <input type="submit" value="Upload" name="submitfile">
            </form>
            </div>
            </div>';
            $ftoken = null;

            // "Make New Folder" Dialog Box
            $ftoken = newtoken(session_id(), $secret);
            echo '<div align="Center" id="newfolderbox" style="visibility:hidden;" class="dialogbox">
            <div class="titlebar">New Folder<img alt="close" class="fileclose" onclick="newfolderclose()" src="/plopbox/images/controls/close.png"></div>
            <div class="boxwrapper">
            <form action="?fileop=2" method="post" enctype="multipart/form-data">
            Name of new folder:<br>
            <input type="hidden" name="ftoken" value="' . $ftoken . '">
            <input type="text" name="foldername" id="foldername"><br>
            <input type="submit" value="Make Folder" name="submitfolder">
            </form>
            </div>
            </div>';
            $ftoken = null;

            // "Move to Trash" Dialog Box
            $ftoken = newtoken(session_id(), $secret);
            echo '<div align="Center" id="trashbox" style="visibility:hidden;" class="dialogbox">
            <div class="titlebar">Move Files to Trash<img alt="close" class="fileclose" onclick="trashboxclose()" src="/plopbox/images/controls/close.png"></div>
            <div class="boxwrapper">
            <form action="?fileop=3" method="post" enctype="multipart/form-data">
            Really delete X files?<br>
            <input type="hidden" name="ftoken" value="' . $ftoken . '">
            <input type="submit" value="Move to Trash" name="trashfiles">
            </form>
            </div>
            </div>';
            $ftoken = null;

            // Navbar, Column, & Wrapper
            echo '<div class="path">' . $paginator . ' ' . $itemcount . ' Items - Browsing ';
            foreach ($navitem as $naventry) {
              echo $naventry . '/';
            }
            echo '</div>';
            echo '<div class="columns"><div class="cname"><a href="' . $interlink . '?sort=' . ($sortval ^ 1) . $smlink . '">Name</a></div><div class="ctime">Last Modified</div><div class="csize">Size</div></div><br>';
            echo '<div class="wrapper">';
          }
          $ctoken = newtoken(session_id(), $secret);
          require "plopbox/usermanager.php";

          // Process files only
          if (isset($dcont['0']) === false) {
            if ($simplemode == false) {
              echo '<div class="dirempty">Directory Empty</div>';
            } else if ($simplemode == true) {
              echo '<h1>Directory Empty</h1>';
            }
            $logmsg .= 'INDEX CORE, OK: EMPTY ';
          } else {
            foreach (array_slice($dcont, $fstart, $_SESSION['flimit']) as $file) {
              // Define the target file
              $ftarget = $droot . '/' . $interlink . $file;
              if (!is_dir($ftarget)) {
                // Inherit simplemode URI arguments in directory links
                $link = $file;
                if (is_dir($ftarget)) {
                  if (isset($_GET['simple'])) {
                    if ($simplemode == true) {
                      $link = $file . '/?simple=true';
                    } else if ($simplemode == false) {
                      $link = $file . '/?simple=false';
                    }
                  }
                }
                // Assign file icon
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
                // Populate file index arrays
                if ($simplemode == true){
                  $files .= '<tr><td><a href="' . htmlentities($link) . '"><img alt="' . $file . '" src="' . $ficon . '"></a></td><td class="indexcolname"><a href="' . htmlentities($link) . '">' . htmlentities($file) . ' ' . $mimed . '</a></td><td class="indexcollastmod">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</td><td class="indexcolsize">' . $fsize . '</td></tr>';
                } else if ($simplemode == false) {
                  $files .= '<div class="entry"><a href="' . rawurlencode($link) . '"><span title="' . htmlentities($file) . '" class="entrylink"></span></a><div class="icon"><img alt="' . $file . '" src="' . $ficon . '"></div> <div class="name">' . htmlentities($file) . ' ' . $mimed . '</div><div class="mtime">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</div><div class="size">' . $fsize . '</div></div>';
                }
              }
            }

            // Process directories only
            foreach ($dcont as $dir) {
              if (preg_match($fileexclude, $dir) === 1) {
                continue;
              } else {
                $ftarget = $droot . '/' . $interlink . $dir;
                if (is_dir($ftarget)) {
                  ++$dcount;
                  $ddcont[] = $dir;
                } else {
                  continue;
                }
              }
            }
            if (!empty($ddcont)) {
              foreach (array_slice($ddcont, $fstart, $_SESSION['flimit']) as $dir) {
                $ftarget = $droot . '/' . $interlink . $dir;
                $link = $dir;
                $fsize = ' ';
                $ficon = $host . '/plopbox/images/directory/folder.png';
                if ($simplemode == true) {
                  $directories .= '<tr><td><a href="' . rawurlencode($link) . '/?simple=true"><img alt="' . $dir . '" src="' . $ficon . '"></a></td>
                  <td class="indexcolname"><a href="' . htmlentities($link) . '/?simple=true">' . htmlentities($dir) . '</a></td>
                  <td class="indexcollastmod">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</td>
                  <td class="indexcolsize">' . $fsize . '</td></tr>';
                } else if ($simplemode == false) {
                  $directories .= '<div class="entry"><a href="' . rawurlencode($link) . '"><span title="' . htmlentities($dir) . '" class="entrylink"></span></a>
                  <div class="icon"><img alt="' . $dir . '" src="' . $ficon . '"></div>
                  <div class="name">' . htmlentities($dir) . '</div>
                  <div class="mtime">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</div>
                  <div class="size">' . $fsize . '</div></div>';
                }
              }
            }

            // Output file index arrays
            echo $directories;
            echo $files;
            $logmsg .= ' INDEX CORE, OK: LISTING ' . $itemcount . ' ITEMS';
          }

          // Begin footer
          if ($simplemode == true){
            echo '</table>';
            echo '<div class="footerspacer"><div class="footer">' . $itemcount . ' Items | <a href="' . $interlink .'?simple=false">Deactivate Simple Mode (Turn CSS &amp; JS On)</a>';
          } else if ($simplemode == false){
            echo '<div class="footerspacer"><div class="footer"><a href="' . $interlink . '?simple=true">Activate Simple Mode (Turn CSS &amp; JS Off)</a>';
          }
          include '/plopbox/footer.html';
          echo '</div></div></div>';
        }
      } else {
        $logmsg .= " INDEX CORE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        $_SESSION['stoken'] = false;
        header("HTTP/4.01 403 Forbidden");
        exit;
      }
    }
  }
} else {
  header("HTTP/4.01 403 Forbidden");
}
?>
