<?php

// Folder name settings
$downloadfoldernames_are_relative = true;
// Download folder
$downloadfoldername = "downloads";
// Completed folder
$completeddownloadfoldername = "downloads/done";

// Logs folder
$logsfoldername = "logs";

// Change file permissions? (e.g. if another user from the same group is supposed to use the file: 0775) 
$chmod = "0775";

// Debug Modes
$showrealtime = true;
$logall = true;

// Report all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//do not change

if ($downloadfoldernames_are_relative) {
	$downloadfolder = realpath("../".$downloadfoldername) . "/";
	$completeddownloadfolder = realpath("../".$completeddownloadfoldername) . "/";
} else {
	$downloadfolder = $downloadfoldername . "/";
	$completeddownloadfolder = $completeddownloadfoldername . "/";
}

$logsfolder = realpath("../".$logsfoldername) . "/";
$functionsfolder = realpath("../functions") . "/";

?>
