<?php
// Global Server Variables for PlopBox.

// $droot: Full filesystem path of your HTTP document-root, without trailing slash.
// This folder should contain "index.php" and the "plopbox" folder.
//Example Path: "C:/Apache/htdocs";
$droot="C:/Apache/htdocs";

// $logpath: Full filesystem path to the PlopBox log folder, with trailing slash.
//Example path: "C:/Apache/htdocs/plopbox/logs/";
$logpath="C:/Apache/htdocs/plopbox/logs/";

// $timezone: Desired timezone to display in file modification times.
//See http://php.net/manual/en/timezones.php for all compatible timezone values.
//Example Values: "America/Chicago"; & "EST";
$timezone="UTC";

// $timestring: Format date string to display in file modification times.
//See http://php.net/manual/en/function.date.php for all formatting options.
//(Uppercase) G = 24 Hours, & (Lowercase) g = 12 Hours
$timestring="M j, Y - g:iA";

// $sort: Default filename sorting direction. Use Scandir sort arguments.
//Values can be: SCANDIR_SORT_ASCENDING; & SCANDIR_SORT_DESCENDING;
$sort=SCANDIR_SORT_ASCENDING;

// $fileexclude: Files to be omitted from the file listing.
// /!\ ***CAUTION*** /!\ Does not affect filesystem permissions. Read technical notes.
//Default value is: "/^(\.htaccess$|\..*|index\.php$|plopbox|\._.*)$/";
$fileexclude="/^(\.htaccess$|\..*|index\.php$|plopbox|\._.*)$/";

// $folderexclude: Directories PlopBox will not index.
// /!\ ***CAUTION*** /!\ Does not affect filesystem permissions. Read technical notes.
//Instead of indexing the directory, PlopBox will output "ACCESS DENIED".
//Default value is: "/^(.plopbox.*)$/";
$folderexclude="/^(.plopbox.*)$/";

// $simplemode: Default activation state of simple mode (No CSS & JS).
//When set to 1, all pages load with simple mode on by default.
//When set to 1, simple mode can be temporarily deactivated via the "?simple=0" URI argument.
//Default value is: 0;
$simplemode=0;

// $mimetypes: Cross-checks file MIME types with known existing MIME type icons.
//If you are adding new icons, you must add the matching filename to this list.
//If a detected MIME type is not found in this list, a default icon is used.
$mimetypes=array("application-epub+zip","application-illustrator","application-javascript","application-mac-binhex40","application-msword-template","application-msword","application-octet-stream","application-pdf","application-pgp-encrypted","application-pgp-keys","application-pgp-signature","application-pkcs7-mime","application-pkcs7-signature","application-postscript","application-relaxng","application-rss+xml","application-rtf","application-sxw","application-vnd-google-earth-kml","application-vnd.iccprofile","application-vnd.ms-access","application-vnd.ms-excel","application-vnd.ms-powerpoint","application-vnd.oasis.opendocument.chart","application-vnd.oasis.opendocument.database","application-vnd.oasis.opendocument.formula-template","application-vnd.oasis.opendocument.formula","application-vnd.oasis.opendocument.graphics","application-vnd.oasis.opendocument.image","application-vnd.oasis.opendocument.presentation-template","application-vnd.oasis.opendocument.presentation","application-vnd.oasis.opendocument.spreadsheet-template","application-vnd.oasis.opendocument.spreadsheet","application-vnd.oasis.opendocument.text-master","application-vnd.oasis.opendocument.text-template","application-vnd.oasis.opendocument.text","application-vnd.openxmlformats-officedocument.wordprocessingml.document","application-vnd.rn-realmedia","application-vnd.scribus","application-vnd.stardivision.calc","application-vnd.stardivision.draw","application-vnd.stardivision.mail","application-vnd.stardivision.math","application-vnd.sun.xml.calc","application-vnd.sun.xml.calc.template","application-vnd.sun.xml.draw","application-vnd.sun.xml.draw.template","application-vnd.sun.xml.impress","application-vnd.sun.xml.impress.template","application-vnd.sun.xml.math","application-vnd.sun.xml.writer.global","application-vnd.sun.xml.writer","application-vnd.sun.xml.writer.template","application-vnd.wordperfect","application-x-7z-compressed","application-x-abiword","application-x-ace","application-x-applix-spreadsheet","application-x-applix-word","application-x-ar","application-x-arc","application-x-archive","application-x-arj","application-x-awk","application-x-bittorrent","application-x-blender","application-x-bzdvi","application-x-bzip-compressed-tar","application-x-bzip","application-x-cd-image","application-x-cda","application-x-chm","application-x-compress","application-x-compressed-tar","application-x-cpio","application-x-cue","application-x-deb","application-x-designer","application-x-desktop","application-x-egon","application-x-executable-script","application-x-executable","application-x-font-afm","application-x-font-bdf","application-x-font-otf","application-x-font-pcf","application-x-font-snf","application-x-font-ttf","application-x-font-type1","application-x-gnumeric","application-x-gzdvi","application-x-gzip","application-x-gzpostscript","application-x-it87","application-x-java-applet","application-x-java-archive","application-x-java","application-x-javascript","application-x-k3b","application-x-kcsrc","application-x-kexi-connectiondata","application-x-kexiproject-shortcut","application-x-kexiproject-sqlite","application-x-kexiproject-sqlite2","application-x-kexiproject-sqlite3","application-x-kformula","application-x-kgetlist","application-x-kontour","application-x-kplato","application-x-krita","application-x-kvtml","application-x-kword","application-x-lha","application-x-lyx","application-x-lzma-compressed-tar","application-x-lzop","application-x-m4","application-x-marble","application-x-mimearchive","application-x-mplayer2","application-x-ms-dos-executable","application-x-mswinurl","application-x-mswrite","application-x-nzb","application-x-object","application-x-pak","application-x-pem-key","application-x-perl","application-x-php","application-x-plasma","application-x-python-bytecode","application-x-qet-element","application-x-qet-project","application-x-quattropro","application-x-rar","application-x-rpm","application-x-ruby","application-x-sharedlib","application-x-shellscript","application-x-shockwave-flash","application-x-siag","application-x-smb-server","application-x-smb-workgroup","application-x-sqlite2","application-x-sqlite3","application-x-srt","application-x-srtrip","application-x-stuffit","application-x-subrip","application-x-tar","application-x-tarz","application-x-tgif","application-x-trash","application-x-troff-man","application-x-tzo","application-x-wmf","application-x-zerosize","application-x-zoo","application-xhtml+xml","application-xml","application-xsd","application-xslt+xml","application-zip","audio-ac3","audio-midi","audio-prs.sid","audio-vn.rn-realmedia","audio-vnd.rn-realvideo","audio-x-adpcm","audio-x-aiff","audio-x-flac+ogg","audio-x-flac","audio-x-generic","audio-x-monkey","audio-x-speex+ogg","audio-x-wav","encrypted","image-svg+xml-compressed","image-svg+xml","image-tiff","image-vnd.dgn","image-vnd.dwg","image-x-adobe-dng","image-x-eps","image-x-generic","image-x-vnd.trolltech.qpicture","image-x-xfig","message-news","message-partial","message-rfc822","message-x-gnu-rmail","message","odf","package-x-generic","text-calendar","text-css","text-csv","text-directory","text-enriched","text-html","text-mathml","text-plain","text-rdf+xml","text-rdf","text-rtf","text-sgml","text-troff","text-vcalendar","text-vnd.abc","text-vnd.wap.wml","text-x-adasrc","text-x-authors","text-x-bibtex","text-x-c++hdr","text-x-c++src","text-x-changelog","text-x-chdr","text-x-cmake","text-x-copying","text-x-csharp","text-x-csrc","text-x-dtd","text-x-generic","text-x-haskell","text-x-hex","text-x-install","text-x-java","text-x-katefilelist","text-x-ldif","text-x-lilypond","text-x-log","text-x-makefile","text-x-nfo","text-x-objchdr","text-x-objcsrc","text-x-pascal","text-x-patch","text-x-po","text-x-python","text-x-qml","text-x-readme","text-x-rpm-spec","text-x-script","text-x-sql","text-x-tcl","text-x-tex","text-x-texinfo","text-x-vcard","text-x-xslfo","text-xmcd","text-xml","unknown","uri-mms","uri-mmst","uri-mmsu","uri-pnm","uri-rtspt","uri-rtspu","video-x-generic","video-x-mng","x-kde-nsplugin-generated","x-media-podcast","x-office-address-book","x-office-calendar","x-office-contact","x-office-document","x-office-presentation","x-office-spreadsheet");

// $mimedebug: Echoes the detected MIME type after each file when set to 1.
//Default value is: 0;
$mimedebug=0;

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
// You are fully responsible for your own system's security, with filesystem permissions etc.
// The values must be Perl Compatible Regular Expressions with delimiters.

//  $timezone is a variable for date_default_timezone_set, and must contain a
// timezone identifier that is valid for that function.
// A complete list of supported identifiers is here: http://php.net/manual/en/timezones.php

//  $mimetypes is a dual-purpose array. Values must be valid MIME types.
// The array is an index of the icons inside the /plopbox/icons/mimetypes folder,
// as well as an index of MIME types known to PlopBox. MIME types and their matching icons
// are cross-checked to the list on a 1:1 ratio, the only differences being slash conversion.
// (IE: "text/plain" becomes "text-plain", as slashes cannot be included in the icon filenames).
?>
