<?php
// Header & Controls
echo '<div class="sunsetheader">
<div class="logo">plopbox</div>
<div class="controlsrow">
<a href="' . $fullurl . '/.."><i id="updir" title="Go Up a Directory" class="controlsrowitem mdi mdi-arrow-up-bold"></i></a>';

if ($perm[1] && $perm[2] === true) {
  $rowicons['select'] = 1;
  echo '<i  id="selectfiles" title="Select Files/Folders" onclick="selectionmodeon()" class="controlsrowitem mdi mdi-checkbox-marked"></i>';
}

if ($perm[1] && $perm[2] && $perm[3] === true) {
  $rowicons['newfolder'] = 1;
  echo '<i id="newdir"  title="Create a New Folder" onclick="newfolderopen()" class="controlsrowitem mdi mdi-folder-plus"></i>';
}

if ($perm[1] && $perm[2] && $perm[5] === true) {
  echo '<i id="newfile" title="Upload a File" onclick="uploadopen()" class="controlsrowitem mdi mdi-note-plus"></i>';
}

if ($perm[1] && $perm[2] &&$perm[6] === true) {
  echo '<i id="del" title="Delete Files/Folders" class="controlsrowitem mdi mdi-delete-forever"></i>';
}
if ($perm[0] && $perm[1] === true) {
  $atoken = newtoken(session_id(), 'ADMIN', $secret);
  echo '<form style="display:none;" id="admin" action="' . $host . '" name="admin" method="post"><input type="hidden" name="atoken" value="' . $atoken  . '"></form>';
  $atoken = null;
  echo '<i title="Settings" onclick="adminopen()" class="controlsrowitem mdi mdi-settings"></i>';
}
echo '<a href="' . $host . '/?logout=true"><i title="Log Out" class="controlsrowitem mdi mdi-power"></i></a>
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
if ($perm[1] && $perm[2] && $perm[6] === true) {
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
echo '<div class="leftcolumn">test</div>';
echo '<div class="columns"><div class="cname"><a href="' . $interlink . '?sort=' . ($sortnameval ^ 1) . $smlink . $startlink . '">' . $sortnamearrow . ' Name</a></div><div class="ctime"><a href="' . $interlink . '?sort=' . $sortdateval . $smlink . $startlink . '">' . $sortdatearrow . ' Last Modified</a></div><div class="csize"><a href="' . $interlink . '?sort=' . $sortsizeval . $smlink . $startlink . '">' . $sortsizearrow . 'Size</div></div><br>';
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
