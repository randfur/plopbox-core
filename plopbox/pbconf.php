<?php
// Global Server Variables for PlopBox.

// $droot: Enter the full filesystem path of your HTTP document-root.
//Do NOT include a trailing slash!
//Example Path: "C:/Apache/htdocs";
$droot="D:/68k";

// $timezone: Enter desired timezone to display in file modification times.
//See http://php.net/manual/en/timezones.php for all compatible timezone values.
//Example Values: "America/Chicago"; & "EST";
$timezone="UTC";

// $timestring: Format date string to display in file modification times.
//See http://php.net/manual/en/function.date.php for all formatting options.
//(Uppercase) G = 24 Hours, & (Lowercase) g = 12 Hours
$timestring="M j, Y - g:iA";

// $sort: Default file sorting. Use Scandir arguments.
//Default value is: SCANDIR_SORT_ASCENDING;
$sort=SCANDIR_SORT_ASCENDING;

// $listexclude: Files to be omitted from the file listing.
// /!\ ***CAUTION*** /!\ Please read the technical notes!
//Default value is: "/^(\.htaccess$|\.|\.\.|index\.php$|plopbox|\.DS_Store$|\._\.DS_Store$|\.AppleDouble$)$/";
$fileexclude="/^(\.htaccess$|\.|\.\.|index\.php$|plopbox|\.DS_Store$|\._\.DS_Store$|\.AppleDouble$)$/";

// $indexexclude: Directories PlopBox will not index.
// /!\ ***CAUTION*** /!\ Does not affect filesystem permissions. Read technical notes.
//Instead of indexing the directory, PlopBox will output "ACCESS DENIED".
//Default value is: "/^(.plopbox.|.plopbox\/icons.)$/";
$folderexclude="/^(.plopbox.|.plopbox\/icons.)$/";

// $simplemode: Default value for simple mode (No CSS & JS).
//When set to 1, all pages load with simple mode on by default.
//When set to 1, simple mode can be temporarily deactivated via "?simple=0" URI argument.
//Default value is: 0;
$simplemode=0;

//---------------------------------------------------------------------------
//////////////////////////////////////////////
// Technical Notes about PlopBox Variables //
////////////////////////////////////////////

//  Note about examples and default values:
// After colons, all characters except '&' are literal, including any punctuation marks.
// An ampersand indicates multiple different examples.

//  When setting $droot, the value can be anything you like, as long as it is the
// uppermost directory you would like to browse, contains both the PlopBox
// index.php and core PlopBox directory. $droot does not neccesarily need to be
// your HTTP document-root.

//  The $fileexclude and $folderexclude variables DO NOT actually deny access to
// anything, these variables ONLY tell PlopBox what to not display.
// Actual file and folder access should be handled with .htaccess files and system permissions.
// The values must be Perl Compatible Regular Expressions with delimiters.

//  $timezone is a variable for date_default_timezone_set, and must contain a
// timezone identifier that is valid for that function.
// A complete list of supported identifiers is here: http://php.net/manual/en/timezones.php
?>
