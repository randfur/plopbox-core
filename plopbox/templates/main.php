<?php
// PlopBox File Index Page Template

// Check Core Mothership Token
if (session_status() == PHP_SESSION_ACTIVE) {
  if (!function_exists('valtoken')) {
    $_SESSION['stoken'] = false;
    syslog(LOG_EMERG, 'PlopBox: SUSPICIOUS ACTIVITY DETECTED: Token Validator not loaded in /plopbox/templates/main.php. ACCESSED BY: ' . $_SERVER['REMOTE_ADDR']);
    header("HTTP/4.01 403 Forbidden");
    exit;
  } else if (function_exists('valtoken')) {
    if (!empty($ctoken)) {
      if (valtoken($db, session_id(), $ctoken, 'MAIN', $secret, $logpath, 10) === false) {
        $logmsg .= " INDEX TEMPLATE, ACCESS DENIED: INVALID/EXPIRED CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        logout($db, $logpath, $logmsg);
        header("HTTP/4.01 403 Forbidden");
        exit;
      } else if (valtoken($db, session_id(), $ctoken, 'MAIN', $secret, $logpath, 10) === true) {
        $ctoken = null;
        if (valstoken($db, session_id(), $_SESSION['uid'], $_SESSION['stoken'], $secret, $logpath, 1800) === false) {
          $logmsg .= " INDEX TEMPLATE, ACCESS DENIED: INVALID/EXPIRED SESSION TOKEN";
          @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
          logout($db, $logpath, $logmsg, ': Session Token for User "' . $_SESSION['user'] . '" is invalid or expired. Logging user out.');
          header("HTTP/4.01 403 Forbidden");
          exit;
        } else {

          // Stylesheet/favicon, header, file manager dialog boxes, & file index
          if ($simplemode == true) {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>PlopBox Index - Browsing ' . $interlink . '</title>
            <link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon"></head>';
            echo '<a href="?logout=true&amp;simple=true">Log Out</a><br>';
            echo 'Browsing ' . $navlinks;

            echo '<br>' . $paginator;
            echo '<br><a href="../?simple=true">Go Up a Directory</a>';
            echo '<table border="1"><tr><td> </td><td><b><a href="' . $interlink . '?sort=' . ($sortnameval ^ 1) . '&amp;simple=true">' . $sortnamearrow . ' Name</a></b></td><td><b><a href="' . $interlink . '?sort=' . $sortdateval . $smlink . $startlink . '">' . $sortdatearrow . ' Last Modified</a></b></td><td><b>Size</b></td></tr>';
          } else if ($simplemode == false) {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">';
            echo '<head><title>PlopBox Index - Browsing ' . $interlink . '</title>
            <link rel="shortcut icon" href="plopbox/images/controls/favicon.gif" type="image/x-icon">
            <meta name="viewport" content="width=500, minimum-scale=0.5, maximum-scale=1.0, user-scalable=yes">
            <link rel="stylesheet" type="text/css" href="plopbox/style.css">
            <form style="display:none;" id="heartbeat" action="/" name="heartbeat" method="post"><input type="hidden" name="heartbeat" value="' . $heartbeat  . '"></form>
            </head>

            <script type="text/javascript" src="/plopbox/jquery/jquery-1.12.1.min.js"></script>
            <script type="text/javascript">
            window.addEventListener("beforeunload", function (heartbeat) {
              $.ajax({
                url:\'' . $host . '\',
                type:\'post\',
                data:$(\'#heartbeat\').serialize(),
              });
            });

            <!-- Dialog box controller -->
            function uploadopen() {
              document.getElementById("uploadbox").style.visibility = "visible";
              document.getElementById("dimthelights").style.visibility = "visible";
            }
            function uploadclose() {
              document.getElementById("uploadbox").style.visibility = "hidden";
              document.getElementById("dimthelights").style.visibility = "hidden";
            }
            function newfolderopen() {
              document.getElementById("newfolderbox").style.visibility = "visible";
              document.getElementById("dimthelights").style.visibility = "visible";
            }
            function newfolderclose() {
              document.getElementById("newfolderbox").style.visibility = "hidden";
              document.getElementById("dimthelights").style.visibility = "hidden";
            }
            function msgclose() {
              document.getElementById("msg").style.visibility = "hidden";
            }
            function selectionmodeon() {
              document.getElementsByClassName("selectors").style.visibility = "visible";
            }
            function selectionmodeoff() {
              document.getElementsByClassName("selectors").style.visibility = "hidden";
            }
            function adminopen() {
              document.getElementById("admin").submit();
            }
            </script>';
            echo '<div id="dimthelights" class="dimthelights" style="visibility:hidden;"></div>';

            // Header & Controls
            echo '<div class="header">
            <div class="logo">plopbox</div>
            <div class="controlsrow">
            <a href="' . $fullurl . '/.."><img alt="Go Up a Directory" id="updir" title="Go Up a Directory" src="/plopbox/images/controls/go-up.png"></a>';

            if ($perm[1] && $perm[2] === true) {
              echo '<img style="cursor:pointer;"  alt="Select Files/Folders" id="selectfiles" title="Select Files/Folders" onclick="selectionmodeon()" src="/plopbox/images/controls/checkbox.png">';
            }

            if ($perm[1] && $perm[2] && $perm[3] === true) {
              echo '<img id="newdir" style="cursor:pointer;" alt="Create a New Folder" title="Create a New Folder" onclick="newfolderopen()" src="/plopbox/images/controls/folder-new.png">';
            }

            if ($perm[1] && $perm[2] && $perm[5] === true) {
              echo '<img style="cursor:pointer;"  alt="Upload a File" id="newfile" title="Upload a File" onclick="uploadopen()" src="/plopbox/images/controls/document-new.png">';
            }

            if ($perm[1] && $perm[2] &&$perm[6] === true) {
              echo '<img style="cursor:pointer;"  alt="Delete Files/Folders" id="del" title="Delete Files/Folders" src="/plopbox/images/controls/delete.png">';
            }
            if ($perm[0] && $perm[1] === true) {
              $atoken = newtoken(session_id(), 'ADMIN', $secret);
              echo '<form style="display:none;" id="admin" action="' . $host . '" name="admin" method="post"><input type="hidden" name="atoken" value="' . $atoken  . '"></form>';
              $atoken = null;
              echo '<img style="cursor:pointer;"  alt="Settings" title="Settings" onclick="adminopen()" src="/plopbox/images/controls/settings.png">';
            }
            echo '<a href="' . $host . '/?logout=true"><img alt="Log Out" id="logout" title="Log Out" src="/plopbox/images/controls/logout.png"></a>
            </div>
            </div>';

            // File Operation Status
            if (!empty($opresult)) {
              echo '<div style="visibility:visible;" id="msg" class="msg">' . $opresult . '<img alt="close" class="msgclose" onclick="msgclose()" src="/plopbox/images/controls/close.png"></div>';
            }

            // "Upload File" Dialog Box
            if ($perm[1] && $perm[2] && $perm[5] === true) {
              $ftoken = newtoken(session_id(), 'FUPLOAD', $secret);
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
            }

            // "Make New Folder" Dialog Box
            if ($perm[1] && $perm[2] && $perm[3] === true) {
              $ftoken = newtoken(session_id(), 'NEWFOLDER', $secret);
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
            }

            // "Move to Trash" Dialog Box
            if ($perm[1] && $perm[2] &&$perm[6] === true) {
              $ftoken = newtoken(session_id(), 'FDELETE', $secret);
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
            }

            // Navbar, Column, & Wrapper
            echo '<div class="path">' . $paginator . ' ' . $itemcount . ' Items - Browsing /';
            foreach ($navitem as $naventry) {
              echo $naventry . '/';
            }
            echo '</div>';
            echo '<div class="columns"><div class="cname"><a href="' . $interlink . '?sort=' . ($sortnameval ^ 1) . $smlink . $startlink . '">' . $sortnamearrow . ' Name</a></div><div class="ctime"><a href="' . $interlink . '?sort=' . $sortdateval . $smlink . $startlink . '">' . $sortdatearrow . ' Last Modified</a></div><div class="csize">Size</div></div><br>';
            echo '<div class="wrapper">';
          }

          // Display output from Index Core
          echo $output;

          // Begin footer
          if ($simplemode == true){
            echo '</table>';
            echo '<div class="footerspacer"><div class="footer">' . $itemcount . ' Items | <a href="' . $interlink .'?simple=false">Deactivate Simple Mode (Turn CSS &amp; JS On)</a>';
          } else if ($simplemode == false){
            echo '<div class="footerspacer"><div class="footer"><a href="' . $interlink . '?simple=true">Activate Simple Mode (Turn CSS &amp; JS Off)</a>';
          }
          include 'plopbox/footer.html';
          echo '</div></div></div>';
        }

      } else {
        $logmsg .= " INDEX TEMPLATE, ACCESS DENIED: MISSING CORE TOKEN (Suspicious!)";
        @file_put_contents($logpath . "pblog.txt", $logmsg . $logmsg3 . PHP_EOL, FILE_APPEND) or logerror();
        logout($db, $logpath, $logmsg);
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
