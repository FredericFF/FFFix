<?php

/*-----------------------------------
Info
The following color codes exist:
- default
- status
- sent
- private_send
- private_received
- error
*-----------------------------------*/

/*-----------------------------------
Settings
*-----------------------------------*/

// Report all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);
ignore_user_abort(true);
register_shutdown_function('xfdie');

/*-----------------------------------
Includes
*-----------------------------------*/

include 'config.php';
include $functionsfolder.'/raw.php';

/*-----------------------------------
Variables
*-----------------------------------*/

$SOH_Char = "\x01";//Start Of Heading
$killed = false;
$handle = '';
$dcc_stream = '';
$timer = time();
$lockfilename = "";
$stream_socket = null;
$logfile = null;

/*-----------------------------------
* Functions
*-----------------------------------*/

function xfwrite ($data, $echo = true) {
	if (substr($data, -1) != "\n") {
		$data .= "\n"; 
	}
	fwrite($GLOBALS['stream_socket'], $data);
	if ($echo) {
		xfecho($data, 'sent');
	}
}

function p ($num) {
	if (isset($GLOBALS['parse'][$num])) {
		return $GLOBALS['parse'][$num];
	}
}

function savetofile () {
	$dccgettemp = '';
	$dccgettemp = fgets($GLOBALS['dcc_stream'], 1024);
	if ($dccgettemp != '') {
		$GLOBALS['dccget'] = $dccgettemp;
		$GLOBALS['currfilesize'] += strlen($dccgettemp);
		fwrite($GLOBALS['handle'], $dccgettemp);
	}
}

function xfecho ($data, string $color = 'default', $ts = 1) {
	if (($data != '') && ($data != "\n") && (file_exists($GLOBALS['logfilename']))) {
		if (substr($data, -1) != "\n") {
			$data .= "\n";
		}

		if ($ts == 1) {
			$write = time() . ' ' . $color . ' ' . $data;
		}
		elseif ($color == '') {
			$write = $data;
		}
		else {
			$write = $color . ' ' . $data;
		}
		fwrite($GLOBALS['logfile'], $write);
		
		if ($GLOBALS['showrealtime']) {
			echo $write; 
		}
	}
}

function xfsetmeta ($key, string $value) {
    if ((file_exists($GLOBALS['metafilename']))) {    
        $arr = json_decode(file_get_contents($GLOBALS['metafilename']),true);    
    } else {
        $arr = array();
    }    
    if (!isset($arr['nick'])) {  
        $arr['nick'] = $GLOBALS['nick'];
    }  
    $arr[$key] = $value;    
    file_put_contents($GLOBALS['metafilename'], json_encode($arr));
}

function xfdie ($status = 'stopped') {
	if ($GLOBALS['killed'] == false) {
		if ($GLOBALS['handle']) { 
			fclose($GLOBALS['handle']); 
		}
		if (($GLOBALS['dcc_stream']) && (!feof($GLOBALS['dcc_stream']))) {
			fclose($GLOBALS['dcc_stream']);
		}
		if ($GLOBALS['lockfilename'] && $GLOBALS['lockfilename'] != '' && file_exists($GLOBALS['lockfilename'])) {
			@unlink($GLOBALS['lockfilename']);
		}
		if (($GLOBALS['stream_socket']) && (!feof($GLOBALS['stream_socket']))) {
			xfwrite('PRIVMSG ' . $GLOBALS['user'] . ' :XDCC REMOVE');
			xfwrite('PRIVMSG ' . $GLOBALS['user'] . ' :XDCC REMOVE ' . $GLOBALS['pack']);
			xfwrite('QUIT :XDCC Fetcher');
			sleep(5);
			if (!feof($GLOBALS['stream_socket'])) {
				fclose($GLOBALS['stream_socket']);
			}
		}
		echo "Stopping DCC\n";
		xfecho('process killed (connection status: ' . connection_status() . ')','error');
		if (isset($GLOBALS['logfile'])) {
    		fclose($GLOBALS['logfile']);
		}
		$GLOBALS['killed'] = true;
		xfsetmeta("status",$status);
		die();
	}
}

/*-----------------------------------
Start
*-----------------------------------*/

// Initialisation variables
foreach ($argv as $arg) {
        $e = explode('=', $arg);
        if (count($e) == 2) {
                $_GET[$e[0]] = $e[1];
	}
        else {
                $_GET[$e[0]] = 0;
	}
}

if (isset($_GET['nick'])) {

    $nick = $_GET['nick'];   
    $filename = $nick.'.json';

    xfecho("Start with nick ".$nick, 'status');
    
    $file_contents = file_get_contents($logsfolder.$filename,true);
       
    if ($file_contents === false) {
        xfecho("Nickfile not loaded", 'error');
        xfdie();
    }    
    $result = (array) json_decode($file_contents);        
   
    $server = $result['server'];
    $port = $result['port'];
    $channel = $result['channel'];
    $user = $result['user'];
    $pack = $result['pack'];
    
    $metafilename = $logsfolder . $nick . '.json';
    $logfilename = $logsfolder . $nick . '.log';
    $delfilename = $logsfolder . $nick . '.del';
        
} else {

    xfecho("Start", 'status');

    $server = ltrim(rtrim($_GET['server']));
    $port = ltrim(rtrim($_GET['port']));
    $channel = ltrim(rtrim($_GET['channel']));
    if (substr($channel, 0, 1) != '#') {
	    $channel = '#' . ltrim(rtrim($channel));
    }
    $user = $_GET['user'];
    $pack = $_GET['pack'];
    if (substr($pack, 0, 1) != '#') {
	    $pack = '#' . ltrim(rtrim($pack));
    }
    
    //test that the control file doesn't exist
    do {
	    $nick = 'xf' . rand(10000,99999);
	    $metafilename = $logsfolder . $nick . '.json';
    } while(file_exists($metafilename));
    
    $logfilename = $logsfolder . $nick . '.log';
    $delfilename = $logsfolder . $nick . '.del';

}


$join = false;
$joined = false;
$requested = false;
$ison = 0;
$percent = -1;
$percent_time = -1;
$last_active_time = 0;

$logfile = fopen($logfilename, 'a');

if (!$logfile) {
    xfecho('Logfile not available');
    xfdie();
}

xfsetmeta("status","initial");
xfsetmeta("server",$server);
xfsetmeta("port",$port);
xfsetmeta("channel",$channel);
xfsetmeta("user",$user);
xfsetmeta("pack",$pack);

while (true) {
	$stream_socket = @fsockopen($server, $port, $errno, $errstr, 30);
	if (!$stream_socket) {
		xfecho($errstr . ': ' . $errno);
		xfdie();
	}
	else {       

		stream_set_blocking($stream_socket, 0); //non blocking
		//rfc 1459
		xfwrite('NICK ' . $nick);
		xfwrite('USER ' . $nick . ' ' . $nick . ' ' .$server . ' :XDCC Fetcher');

		while (!feof($stream_socket)) {
		        if ($last_active_time != time()) {
		                $last_active_time = time();
		                xfsetmeta("last_active_time",$last_active_time);
			}

			if (!file_exists($logfilename)) {
				xfdie();
			}
			if (file_exists($delfilename)) {
				@unlink($delfilename);
				sleep(2);
				fclose($logfile);
				@unlink($logfilename);
				xfdie();
			}

	    		$write = NULL;
    			$except = NULL;
			$streams = array($stream_socket);
			stream_select($streams, $write, $except, 3);
			$get = fgets($stream_socket);
			//------------------------------------------------
			CheckRaw($get);
			//------------------------------------------------
			$parse = explode(' ', $get);

			if (rtrim($get) != '' && p(0) != 'PING') {

				if (p(2) == $nick) {
					xfecho($get,'private_received');
				}
				else if ($logall) {
					xfecho($get);
				}
			}
			if (p(0) == 'PING') {
				xfecho('PING? PONG!','status');
				xfwrite('PONG ' . substr(p(1), 1),false);
			}
			elseif ((p(1) == 'PRIVMSG') && (p(2) == $nick) && (p(3) == ':STOPXF')) {
				xfecho('Manual abort');
				xfdie();
			}
			elseif ((p(1) == 'PRIVMSG') && (p(2) == $nick) && (p(3) == ':COMMANDXF')) {
				$string = '';
				for ($x = 4; $x < count($parse); $x++) {
					$string .= $parse[$x] . ' ';
				}
				if ($string != '') {
					xfwrite(rtrim($string));
				}
			}
			elseif ((stristr($get,$user)) && (stristr($get, 'all slots full')) 
				&& (!stristr($get, 'Added you')) && (p(1) == 'NOTICE') && (p(2) == $nick)) {
				$timer = time() + 30;
			}
			elseif ($joined && ((time() >= $timer) && ($timer != 0))) {
				$timer = time() + 30;
				xfecho("Requesting...",'status');
				xfwrite('PRIVMSG ' . $user . ' :' . $SOH_Char . 'XDCC SEND ' . $pack . $SOH_Char);
			}
			elseif ((stristr(p(0),$user)) && (p(3) == ':' . $SOH_Char .'DCC')) {
				if (p(4) == 'SEND') {
					echo "Starting DCC...\n";
					xfsetmeta("status","starting DCC");
					$DCCfilesize = (int)(substr(p(8), 0, -3));
					$DCCfilename = p(5);
					$DCCip = long2ip(p(6));
					$DCCport = p(7);
					$filename = $downloadfolder . $DCCfilename;
					xfsetmeta("filename",$DCCfilename);
					xfsetmeta("filesize",$DCCfilesize);
				}
				elseif (p(4) == 'ACCEPT') {
					echo "Resume accepted...\n";
					xfsetmeta("status","resume accepted");
				}
				if ((file_exists($filename)) && (p(4) == 'SEND')) {
					if (filesize($filename) >= $DCCfilesize) {
						xfecho('File already downloaded');
						xfsetmeta("status","file already downloaded");
						xfdie();
					}
					xfecho('Attempting resume...','status');
					xfsetmeta("status","attempting resume");
					xfwrite('PRIVMSG ' . $user . ' :' . $SOH_Char . 'DCC RESUME ' . $DCCfilename . ' ' . $DCCport . ' ' . filesize($filename) . $SOH_Char);
				}
				else {
					xfecho('Connecting to ' . $DCCip . ' on port ' . $DCCport . ' (' . $DCCfilesize . ' bytes)...','status');
					xfsetmeta("status","connecting");
					//DCC stream
					$dcc_stream = @fsockopen($DCCip, $DCCport, $errno, $errstr, 30);
					if (!$dcc_stream) {
						xfecho($errstr . ': ' . $errno);
						xfdie();
					}
					else {
						stream_set_blocking($dcc_stream,0);
						xfecho('Connected...','status');
						xfsetmeta("status","connected");
						$filename = $downloadfolder . $DCCfilename;
						if (file_exists($filename . '.lck')) {
							$filename = $downloadfolder . $nick . '.sav';
						}

						$lockfilename = $filename . '.lck';

						$handle = fopen($lockfilename, 'a');
						fclose($handle);

						if (file_exists($filename)) {
							$currfilesize = filesize($filename);
						}
						else {
							$currfilesize = 0;
						}
						$handle = fopen($filename, 'a');

						if ($handle === false) {
							xfecho("Unable to open ".$filename, 'error');
							xfdie();
						} else {
							xfecho("Opened ".$filename, 'status');
						}

						while (!feof($dcc_stream)) {
		                                        xfsetmeta("status","downloading");
							savetofile();
							if (!feof($stream_socket)) {
								$get = fgets($stream_socket);
								if ($get) {
									$parse = explode(' ', $get);
									if (p(0) == 'PING') {
										xfecho($get);
										xfwrite('PONG ' . substr(p(1), 1));
									}
									elseif ((p(1) == 'PRIVMSG') && (p(2) == $nick) && (p(3) == ':STOPXF')) {
										xfecho('Manual abort');
										xfsetmeta("status","manual abort");
										xfdie();
									}
									elseif ((p(1) == 'PRIVMSG') && (p(2) == $nick) && (p(3) == ':COMMANDXF')) {
										$string = '';
										$x = 4;
										while (p(x) != '') {
											$string .= $p($x) . ' ';
											$x++;
										}
										if ($string != '') {
											xfwrite(rtrim($string));
										}
									}
								}
							}
							if ($currfilesize != 0) {
								$currpercent = (($currfilesize / $DCCfilesize) * 100);
							}
							else {
								$currpercent = 0;
							}
							if ($currpercent > $percent && $percent_time != time()) {
								$percent = $currpercent;
								$percent_time = time();
								xfecho($percent . '% completed - ' . $DCCfilename . ' - ' . $nick, '', 'status');
								xfsetmeta("completion",$currpercent);
							}
							if (!file_exists($logfilename)) {
								xfdie();
							}
							elseif (file_exists($delfilename)) {
								@unlink($delfilename);
								sleep(2);
								fclose($logfile);
								@unlink($logfilename);
								xfdie();
							}
							elseif ($currfilesize >= $DCCfilesize) {
								xfecho('Downloaded!','status');
								xfsetmeta("status","done");
								xfsetmeta("completion","100");
								if (rename($downloadfolder . $DCCfilename,$completeddownloadfolder . $DCCfilename) === false) {
    								xfecho('Failed to move'." ".$downloadfolder.$DCCfilename." -> ".$completeddownloadfolder.$DCCfilename);
									xfdie("done (failed to move)");
								}
								xfdie("done");
							}
							elseif ($currfilesize > $DCCfilesize) {
								xfecho('Current filesize is greater than expected! Aborting.');
	    							xfsetmeta("completion","100");
								xfdie("done (filesize bigger)");
							}
							$dccarr = array($dcc_stream);
							@stream_select($dccarr, $write = NULL, $except = NULL, 3);
						}
						if (filesize($filename) < $DCCfilesize) {
							xfecho('Aborted.');
							xfsetmeta("status","aborted");
							fclose($dcc_stream);
							@unlink($lockfilename);
							fclose($handle);
							xfwrite('PRIVMSG ' . $user . ' :' . $SOH_Char . 'XDCC SEND ' . $pack . $SOH_Char);
						}
					}
				}
			}
		}
	}

	xfecho('Disconnected from server! Reconnecting in 60 seconds...','error');
	sleep(60);
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
