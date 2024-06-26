<?php
  
    include 'config.php'; 

	$arr = array();
	$handler = opendir($logsfolder);
    $result = array();
    
	$counter = 0;
	while ($filename = readdir($handler)) {
		if (pathinfo($filename, PATHINFO_EXTENSION) == "json") {                						 
            $result[] = json_decode(file_get_contents($logsfolder.$filename,true));    
        }
    } 
  
	echo json_encode($result);
	closedir($handler);

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
