<?php
// PlopBox File Indexing Core
$start = explode(' ', microtime())[0] + explode(' ', microtime())[1];
// Initialize core runtime variables
include "/plopbox/pbconf.php";
date_default_timezone_set( $timezone );
$interlink = urldecode(strstr( $_SERVER['REQUEST_URI'], "?", true ) ?: $_SERVER['REQUEST_URI']);
$interlink = rtrim($interlink, "index.php");
$host = ('http://' . $_SERVER['SERVER_NAME']);
$logfile = @fopen($logpath . "access.txt", "a") or syslog(LOG_ERR, 'PlopBox: ERROR writing PlopBox access log! Make sure filesystem permissions for PHP are set to read/write in the specified log directory.');
$logmsg = date("M d Y, G:i:s e", $_SERVER['REQUEST_TIME']) . ' | ' . $_SERVER['REMOTE_ADDR'] . ' | ' . $_SERVER['HTTP_USER_AGENT'] . ' -> ' . $host . $interlink . ' | ';

// Stop execution if specified directory is an excluded directory
if (preg_match($folderexclude, $interlink) === 1) {
  $logmsg .= 'DENIED' . PHP_EOL;
  @fwrite($logfile, $logmsg);
  @fclose($logfile);
  exit('<h3>ACCESS DENIED</h3><br>You are not allowed to view ' . $interlink);
}

// Parse ?sort URI argument
if (isset($_GET['sort'])) {
  if ($_GET['sort'] == 1) { $sort = str_replace('1', SCANDIR_SORT_DESCENDING, $_GET['sort']); }
  $sortval = $_GET['sort'];
} else {
  $sortval = 0;
}
// Parse ?simplemode URI argument
if (isset($_GET['simple'])) {
  if ($_GET['simple'] === '1') {
    $simplemode = 1;
  } else if ($_GET['simple'] === '0') {
    $simplemode = 0;
  }
}

// Load stylesheet, header, and favicon
echo '<link rel="shortcut icon" href="/plopbox/icons/favicon.gif" type="image/x-icon"/>';
if ($simplemode === 1) {
    echo 'Browsing: ' . $interlink;
    echo '<br><a href="../?simple=1">Go Up a Directory</a>';
    echo '<table><tr><td> </td><td><b>Name</b></td><td><b>Last Modified</b></td><td><b>Size</b></td></tr>';
} else if ($simplemode === 0){
  echo '<link rel="stylesheet" type="text/css" href=' . $host . '/plopbox/style.css />';
  include '/plopbox/header.html';
  echo '<div class="path">Browsing ' . $interlink . '</div>';
  echo '<div class="columns"><div class="cname"><a href=' . $interlink . '?sort=' . ($sortval ^ 1) . '>Name</a></div><div class="ctime">Last Modified</div><div class="csize">Size</div></div><br>';
  echo '<div class="wrapper">';
}

// Scan directory specified in the URI
$directories = "";
$files = "";
$count = "0";
$dcont = scandir(($droot . $interlink), $sort);
if (isset($dcont['2']) === FALSE) {
  echo '<div class="dirempty">Directory Empty</div>';
  $logmsg .= 'OK EMPTY ' . PHP_EOL;
} else {
  foreach ($dcont as $file) {
    // Skip excluded files, and count non-excluded files.
    if (preg_match($fileexclude, $file) === 1) {
      continue;
    } else {
      $count++;
    }
    // Define the target file
	$ftarget = ($droot . '/' . $interlink . $file);
// Inherit simplemode URI arguments for directory link
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
  // Assign file icon
	if (is_dir($ftarget)) {
		$ficon = ($host . '/plopbox/icons/directory/folder.png');
	} else {
    $mime = strstr(finfo_file(finfo_open(FILEINFO_MIME), $ftarget ), ';', true);
    $mime = str_replace('/', '-', $mime);
    $mimed = "";
    if ($mimedebug === 1) {$mimed = $mime;}
		if (in_array($mime, $mimetypes)) {
			$ficon =  ($host . '/plopbox/icons/mimetypes/' . $mime . '.png');
		} else {
			$ficon = ($host . '/plopbox/icons/mimetypes/application-x-zerosize.png');
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
$logmsg .= 'OK ' . $count . ' ITEMS ' . PHP_EOL;
}

// Write to access log
@fwrite($logfile, $logmsg);
@fclose($logfile);

// Begin footer
if ($simplemode === 1){
  echo '</table>';
  echo ('<br></div><div class="footer">' . $count . ' Items in Directory<br><a href="' . $interlink .'?simple=0">Deactivate Simple Mode (Turn CSS & JS On)</a>');
} else if ($simplemode === 0){
  echo ('<br></div><div class="footer">' . $count . ' Items in Directory<br><a href="' . $interlink . '?simple=1">Activate Simple Mode (Turn CSS & JS Off)</a>');
}
include '/plopbox/footer.html';
echo('<br>Index generated in ' . round((explode(' ', microtime())[0] + explode(' ', microtime())[1]) - $start, 4) . ' seconds.</div>');
?>
