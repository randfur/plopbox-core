<?php
// PlopBox File Indexing Core

$start = explode(' ', microtime())[0] + explode(' ', microtime())[1];
// Format URI & define default variables
include "/plopbox/pbconf.php";
$interlink = strstr( $_SERVER['REQUEST_URI'], "?", true ) ?: $_SERVER['REQUEST_URI'];
$interlink = urldecode($interlink);
$host = ('http://' . $_SERVER['SERVER_NAME']);
echo '<link rel="shortcut icon" href="/plopbox/icons/favicon.gif" type="image/x-icon"/>';
date_default_timezone_set( $timezone );

// Stop if current directory is an excluded directory
if (preg_match($folderexclude, $interlink) === 1) { exit('ACCESS DENIED'); }

// Check sort argument
if (isset($_GET['sort'])) {
  $sort = str_replace('1', SCANDIR_SORT_DESCENDING, $_GET['sort']);
}
// Check simplemode argument
if (isset($_GET['simple'])) {
  if ($_GET['simple'] === '1') {
        $simplemode = 1;
      } else if ($_GET['simple'] === '0') {
          $simplemode = 0;
        }
      }

// Header and stylesheet
if ($simplemode === 1) {
    echo 'Browsing: ' . $interlink;
    echo '<br><a href="../?simple=1">Go Up a Directory</a>';
    echo '<table><tr><td> </td><td><b>Name</b></td><td><b>Last Modified</b></td><td><b>Size</b></td></tr>';
} else if ($simplemode === 0){
  include '/plopbox/header.html';
  echo '<div class="path">  ' . $interlink . '</div>';
  echo '<link rel="stylesheet" type="text/css" href=' . $host . '/plopbox/style.css />';
  echo '<div class="columns"><div class="cname">Name</div><div class="ctime">Last Modified</div><div class="csize">Size</div></div><br>';
}
// Scan current directory
$dcont= scandir(($droot . $interlink), $sort);
if (isset($dcont['3']) === FALSE) {
  echo '<h1 class="dirempty">Directory Empty</h1>';
} else {
foreach ($dcont as $file) {
    // Skip unwanted entries
    if (preg_match($fileexclude, $file) === 1) { continue; }
	$ftarget = ($droot . '/' . $interlink . $file);
// Inherit simplemode URI arguments
$link = $file;
if (is_dir($ftarget)) {
  if (isset($_GET['simple'])) {
    if ($simplemode === 1) {
    $link = ($file . '/?simple=1');
  } else if ($simplemode === 0) {
    $link = ($file . '/?simple=0');
  }
}
}
  // Assign filetype icon
	if (is_dir($ftarget)) {
		$ficon = ($host . '/plopbox/icons/dir.gif');
	} else {
		$extensioncheck = (pathinfo($ftarget, PATHINFO_EXTENSION));
		if ($extensioncheck ==! "") {
			$ficon =  ($host . '/plopbox/icons/' . $extensioncheck . '.gif');
		} else {
			$ficon = ($host . '/plopbox/icons/default.gif');
    }
		}
  // Generate filesize
  if (is_dir($ftarget)) {
    $fsize = ' ';
  } else {
    $fsize = filesize($ftarget);
    $dec =  2;
    $csize = 'BKMGTP';
    $sizefactor = floor((strlen($fsize) - 1) / 3);
    $fsize = sprintf("%.{$dec}f", $fsize / pow(1024, $sizefactor)) . ' <div class="sizefactor">' . @$csize[$sizefactor] . '</div>';
  }
	// Output file & folder entry
  if ($simplemode === 1) {
    echo ('<tr><td><a href="' . htmlentities($link) . '"><img src="' . $ficon . '" /></a></td><td class="indexcolname"><a href="' . htmlentities($link) . '">' . htmlentities($file) . '</a></td><td class="indexcollastmod">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</td><td class="indexcolsize">' . $fsize . '</td></tr>');
  } else if ($simplemode === 0){
    echo ('<div class="entry"><div class="selectors"><input id="' . $interlink . $file . '" type="checkbox" name="' . $file . '" value="' . $file . '"></div><div class="icon"><a href=' . rawurlencode($link) . '><img src="' . $ficon . '" /></a></div> <div class="name"><a href="' . htmlentities($link) . '">' . htmlentities($file) . '</a></div><div class="mtime">' . date( "M j, Y - g:iA", filemtime($ftarget)) . '</div><div class="size">' . $fsize . '</div></div>');
  }
}
}

// Simple mode link
if ($simplemode === 1){
  echo '</table>';
  echo ('<br></div><div class="footer"><a href="' . $interlink .'?simple=0">Deactivate Simple Mode (CSS & JS ON)</a>');
} else if ($simplemode === 0){
  echo ('<br><div class="footer"><a href="' . $interlink . '?simple=1">Activate Simple Mode (CSS & JS OFF)</a>');
}

// Footer
include '/plopbox/footer.html';
echo('<br>Index generated in ' . round((explode(' ', microtime())[0] + explode(' ', microtime())[1]) - $start, 4) . ' seconds.</div>');
?>
