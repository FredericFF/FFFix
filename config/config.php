<?php

// Download folder
$downloadfoldername = "downloads";

// Logs folder
$logsfoldername = "logs";

// Debug Modes
$showrealtime = true;
$logall = true;

// Report all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//do not change
$downloadfolder = realpath("../".$downloadfoldername) . "/";
$logsfolder = realpath("../".$logsfoldername) . "/";
$functionsfolder = realpath("../functions") . "/";

?>
