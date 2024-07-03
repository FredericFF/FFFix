<!DOCTYPE html>
<html>
	<head>
		<title>FFFix Log</title>
		<link rel="stylesheet" href="../web/main.css">
	</head>
	<body class="omgcontainer">
<?php

include "config.php";

$logfilename = $logsfolder . $_GET["log"];

if (file_exists($logfilename)) {
	echo "Server time: " . date("Y-m-d H:i:s") . "<br><br>";

	$logfile = fopen($logfilename,"r");
	if (filesize($logfilename) > 0) {
		$line = explode("\n",fread($logfile,filesize($logfilename)));
		$counter = 0;
		while (isset($line[$counter])) {
			if ($line[$counter] != "") {
				$parse = explode(" ",$line[$counter]);
				if (substr($parse[0],0,8) == "<script>") {
					echo $line[$counter] . "\n";
				}
				else {
					$startparse = 2;
//					$echoline = "<span style=\"color:" . $parse[1] . "\">";

					$echoline = "";

					$string = "";
					for ($x=$startparse; $x<count($parse); $x++) { 
					    $string .= $parse[$x] . " "; 
				    	}

					try {
    					    $date = date("[Y-m-d H:i:s]",$parse[0]);
					} catch (exception $e) {
					}	

					$echoline .= "<span class=\"log_time\">".$date . "</span><span class=\"log_".$parse[1]."\">&nbsp;" . rtrim($string) . "</span><br>";

					echo $echoline;
				}
			}
			$counter++;
		}
	}
	fclose($logfile);
}
else {
	echo "File does not exist";
}

/*
	This file is part of XDCC Fetcher.

        XDCC Fetcher is free software: you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation, either version 2 of the License, or
        (at your option) any later version.

        XDCC Fetcher is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with XDCC Fetcher.  If not, see <http://www.gnu.org/licenses/>.
*/

?>

    </body>
</html>
