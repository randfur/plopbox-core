<?php
// Header
echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">';
echo '<head><title>PlopBox Index - Settings</title>
<link rel="shortcut icon" href="/plopbox/images/controls/favicon.gif" type="image/x-icon">
<meta name="viewport" content="width=500, minimum-scale=0.5, maximum-scale=1.0, user-scalable=yes">
<link rel="stylesheet" type="text/css" href="' . $host . '/plopbox/style.css"></head>';
echo '<script type="text/javascript">
function msgclose() {
  document.getElementById("msg").style.visibility = "hidden";
}
function adminopen() {
  document.getElementById("admin").submit();
}
function ssopen() {
  document.getElementById("adminss").submit();
}
function umopen() {
  document.getElementById("adminum").submit();
}
</script>
<div class="header">
<div class="logo">plopbox</div>
<div class="controlsrow">
<a href="' . $host . '"><img alt="View File Index" title="View File Index" src="/plopbox/images/controls/view-list-details.png"></a>
<a href="' . $host . '/?logout=true"><img alt="Log Out" id="logout" title="Log Out" src="/plopbox/images/controls/logout.png"></a>
</div>
</div>';

// Tab Selection
$style1 = $style2 = $style3 = $text1 = $text2 = $text3 = '';
if (!isset($_POST['sstoken']) && !isset($_POST['umtoken'])) {
  $style1 = 'style="background-color:lightgrey;"';
  $text1 = 'style="color:white;"';
}
if (isset($_POST['sstoken']) && !isset($_POST['umtoken'])) {
  $style2 = 'style="background-color:lightgrey;"';
  $text2 = 'style="color:white;"';
}
if (!isset($_POST['sstoken']) && isset($_POST['umtoken'])) {
  $style3 = 'style="background-color:lightgrey;"';
  $text3 = 'style="color:white;"';
}

// Tabs
echo '<div class="buttonbar">';
if ($perm[0] && $perm[1] === true) {
  $atoken = newtoken(session_id(), 'ADMIN', $secret);
  echo '<form style="display:none;" id="admin" action="' . $host . '" name="admin" method="post"><input type="hidden" name="atoken" value="' . $atoken  . '"></form>';
  $atoken = null;
  echo '<div ' . $style1 . ' class="spagebutton" onclick="adminopen()"><div class="spagebuttontext">Personal Settings</div></div>';
}
if ($perm[7] && $perm[8] === true) {
  $atoken = newtoken(session_id(), 'ADMIN', $secret);
  $sstoken = newtoken(session_id(), 'SERVERSETTINGS', $secret);
  echo '<form style="display:none;" id="adminss" action="' . $host . '" name="adminss" method="post"><input type="hidden" name="atoken" value="' . $atoken  . '"><input type="hidden" name="sstoken" value="' . $sstoken  . '"></form>';
  $atoken = $sstoken = null;
  echo '<div ' . $style2 . ' class="spagebutton" onclick="ssopen()"><div class="spagebuttontext">Server Settings</div></div>';
}
if ($perm[7] === true) {
  $atoken = newtoken(session_id(), 'ADMIN', $secret);
  $umtoken = newtoken(session_id(), 'USERMANAGER', $secret);
  echo '<form style="display:none;" id="adminum" action="' . $host . '" name="adminum" method="post"><input type="hidden" name="atoken" value="' . $atoken  . '"><input type="hidden" name="umtoken" value="' . $umtoken  . '"></form>';
  $atoken = $umtoken = null;
  echo '<div ' . $style3 . ' class="spagebutton" onclick="umopen()"><div class="spagebuttontext">User Management</div></div>';
}

echo '</div>
<div class="swrapper">
<div class="scolumn">';

// Personal Settings Page
if ($page === 0) {
  echo 'Personal Settings<br>
  <form>
  Max Items per Page: <select name="flimit">
  <option value="50" ' . $sel50 . '>50</option>
  <option value="100" ' . $sel100 . '>100</option>
  <option value="200" ' . $sel200 . '>200</option>
  <option value="300" ' . $sel300 . '>300</option>
  <option value="500" ' . $sel500 . '>500</option>
  <option value="1000" ' . $sel1000 . '>1000 (May Cause Lag)</option>
  </select><br>';

  // Server Settings Page
} else if ($page === 1) {
  echo 'Server Settings.<br>
  Directory Root:
  <form action="" method=""><input type="text">
  <br>
  Log Path:
  <br>
  Timezone:
  <br>
  Time String:
  <br>
  File Exclusions:
  <br>
  Folder Exclusions:
  <br>
  MIME Debug Mode:
  <br>
  Simple Mode Default:
  </form>';

  // User Management Page
} else if ($page === 2) {
  echo '<div class="scontrolwrapper"><img style="padding-top:9px; padding-left:3px;" class="scontrol" src="/plopbox/images/controls/system-users.png"><div style="margin-left:40px;" class="scontrol"> User Management</div><img style="float:right; padding-top:5px;" src="/plopbox/images/controls/list-add-user.png"><img style="float:right; padding-top:5px;" src="/plopbox/images/controls/list-remove-user.png"></div>
  <div class="umcolumns"><div class="scname">Username</div><div class="scperms">Permissions</div><div class="sctime">Last Seen</div></div><div class="sentrywrapper">';
  echo $output;
}
echo "</div></div></div>";
?>
