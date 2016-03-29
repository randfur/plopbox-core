<?php
// PlopBox Filebrowser Index Core

// Check Core Mothership Token
if (!function_exists('valtoken')) {
  $_SESSION['valid'] = false;
  syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/core.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
  header("HTTP/1.0 403 Forbidden");
  exit;
} else if (function_exists('valtoken')) {
  if (!empty($ctoken)) {
  if (valtoken($ctoken, $secret, 10) == false) {
    $logmsg .= " INDEX CORE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
    @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
    $_SESSION['valid'] = false;
    header("HTTP/1.0 403 Forbidden");
    exit;
  } else if (valtoken($ctoken, $secret, 10) == true) {
    $ctoken = null;
    $start = explode(' ', microtime())[0] + explode(' ', microtime())[1];

    // Load stylesheet/favicon, header, & file manager dialog boxes
    echo '<link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon"/>';
    if ($simplemode === 1) {
      echo 'Browsing: ' . $interlink;
      echo '<br><a href="../?simple=1">Go Up a Directory</a>';
      echo '<table><tr><td> </td><td><b>Name</b></td><td><b>Last Modified</b></td><td><b>Size</b></td></tr>';
    } else if ($simplemode === 0){
      echo '<link rel="stylesheet" type="text/css" href=' . $host . '/plopbox/style.css />';
      include '/plopbox/header.html';

      // "Upload File" Dialog Box
      $ftoken = newtoken();
      echo '<div align="Center" id="uploadbox" style="visibility:hidden;" class="dialogbox">
      <div class="titlebar">Upload File<img class="fileclose" onclick="uploadclose()" src="/plopbox/images/controls/close.png"></div>
      <div class="boxwrapper">
      <form action="/plopbox/uploader.php" method="post" enctype="multipart/form-data">
      Select file to upload:<br>
      <input type="hidden" name="ftoken" value="' . $ftoken . '">
      <input type="file" name="fileToUpload" id="fileToUpload"><br>
      <input type="submit" value="Upload" name="submitfile">
      </form>
      </div>
      </div>';

      // "Make New Folder" Dialog Box
      $ftoken = newtoken();
      echo '<div align="Center" id="newfolderbox" style="visibility:hidden;" class="dialogbox">
      <div class="titlebar">New Folder<img class="fileclose" onclick="newfolderclose()" src="/plopbox/images/controls/close.png"></div>
      <div class="boxwrapper">
      <form action="/plopbox/newfolder.php" method="post" enctype="multipart/form-data">
      Name of new folder:<br>
      <input type="hidden" name="ftoken" value="' . $ftoken . '">
      <input type="text" name="foldername" id="foldername"><br>
      <input type="submit" value="Make Folder" name="submitfolder">
      </form>
      </div>
      </div>';

      // "Move to Trash" Dialog Box
      $ftoken = newtoken();
      echo '<div align="Center" id="trashbox" style="visibility:hidden;" class="dialogbox">
      <div class="titlebar">Move Files to Trash<img class="fileclose" onclick="trashboxclose()" src="/plopbox/images/controls/close.png"></div>
      <div class="boxwrapper">
      <form action="/plopbox/newfolder.php" method="post" enctype="multipart/form-data">
      Really delete X files?<br>
      <input type="hidden" name="ftoken" value="' . $ftoken . '">
      <input type="submit" value="Move to Trash" name="trashfiles">
      </form>
      </div>
      </div>';
      echo '<div class="path">Browsing ' . $interlink . '</div>';
      echo '<div class="columns"><div class="cname"><a href=' . $interlink . '?sort=' . ($sortval ^ 1) . $sslink . '>Name</a></div><div class="ctime">Last Modified</div><div class="csize">Size</div></div><br>';
      echo '<div class="wrapper">';
    }
    $ftoken = null;

    // Scan directory specified in the URI
    $count = "0";
    $dcont = scandir(($droot . $interlink), $sort);
    if (isset($dcont['2']) === FALSE) {
      echo '<div class="dirempty">Directory Empty</div>';
      $logmsg .= 'INDEX CORE, OK: EMPTY ';
    } else {
      foreach ($dcont as $file) {
        // Skip excluded files, and count non-excluded files.
        if (preg_match($fileexclude, $file) === 1) { continue; } else { $count++; }
        // Define the target file
        $ftarget = $droot . '/' . $interlink . $file;
        // Inherit simplemode URI arguments for directory link
        $link = $file;
        if (is_dir($ftarget)) {
          if (isset($_GET['simple'])) {
            if ($simplemode === 1) {
              $link = $file . '/?simple=1';
            } else if ($simplemode === 0) { $link = $file . '/?simple=0'; }
          }
        }
        // Assign file icon
        if (is_dir($ftarget)) {
          $ficon = $host . '/plopbox/images/directory/folder.png';
        } else {
          $mime = strstr(finfo_file(finfo_open(FILEINFO_MIME), $ftarget ), ';', true);
          $mime = str_replace('/', '-', $mime);
          $mimed = "";
          if ($mimedebug === 1) {$mimed = ' - ' . $mime;}
          if (in_array($mime, $mimetypes)) {
            $ficon =  $host . '/plopbox/images/mimetypes/' . $mime . '.png';
          } else {
            $ficon = $host . '/plopbox/images/mimetypes/application-x-zerosize.png';
          }
        }
        // Calculate filesize
        if (is_dir($ftarget)) {
          $fsize = ' ';
        } else {
          $fsize = filesize($ftarget);
          $dec =  2;
          $csize = 'BKMGTP';
          $sizefactor = floor((strlen($fsize) - 1) / 3);
          $fsize = sprintf("%.{$dec}f", $fsize / pow(1024, $sizefactor)) . ' <div class="sizefactor">' . @$csize[$sizefactor] . '</div>';
        }
        // Populate file index arrays
        if ($simplemode === 1){
          if (is_dir($ftarget)){
            $directories .= '<tr><td><a href="' . htmlentities($link) . '"><img src="' . $ficon . '" /></a></td><td class="indexcolname"><a href="' . htmlentities($link) . '">' . htmlentities($file) . '</a></td><td class="indexcollastmod">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</td><td class="indexcolsize">' . $fsize . '</td></tr>';
          } else {
            $files .= '<tr><td><a href="' . htmlentities($link) . '"><img src="' . $ficon . '" /></a></td><td class="indexcolname"><a href="' . htmlentities($link) . '">' . htmlentities($file) . ' ' . $mimed . '</a></td><td class="indexcollastmod">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</td><td class="indexcolsize">' . $fsize . '</td></tr>';
          }
        } else if ($simplemode === 0){
          if (is_dir($ftarget)) {
            $directories .= '<div class="entry"><div class="selectors"><input id="' . $interlink . $file . '" type="checkbox" name="' . $file . '" value="' . $file . '"></div><div class="icon"><a href=' . rawurlencode($link) . '><img src="' . $ficon . '" /></a></div> <div class="name"><a href="' . htmlentities($link) . '">' . htmlentities($file) . '</a></div><div class="mtime">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</div><div class="size">' . $fsize . '</div></div>';
          } else {
            $files .= '<div class="entry"><div class="selectors"><input id="' . $interlink . $file . '" type="checkbox" name="' . $file . '" value="' . $file . '"></div><div class="icon"><a href=' . rawurlencode($link) . '><img src="' . $ficon . '" /></a></div> <div class="name"><a href="' . htmlentities($link) . '">' . htmlentities($file) . ' ' . $mimed . '</a></div><div class="mtime">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</div><div class="size">' . $fsize . '</div></div>';
          }
        }
      }
      // Output file index arrays
      echo $directories;
      echo $files;
      $logmsg .= 'INDEX CORE, OK: ' . $count . ' ITEMS ';
    }

    // Begin footer
    if ($simplemode === 1){
      echo '</table>';
      echo '<br></div><div class="footer">' . $count . ' Items in Directory<br><a href="' . $interlink .'?simple=0">Deactivate Simple Mode (Turn CSS & JS On)</a>';
    } else if ($simplemode === 0){
      echo '<br></div><div class="footer">' . $count . ' Items in Directory<br><a href="' . $interlink . '?simple=1">Activate Simple Mode (Turn CSS & JS Off)</a>';
    }
    include '/plopbox/footer.html';
    echo '<br>Index generated in ' . round((explode(' ', microtime())[0] + explode(' ', microtime())[1]) - $start, 4) . ' seconds.</div>';
  }
} else {
  $logmsg .= " INDEX CORE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
  @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
  $_SESSION['valid'] = false;
  header("HTTP/1.0 403 Forbidden");
  exit;
}
}
?>
