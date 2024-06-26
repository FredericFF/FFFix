<?php

/*
*   Plugin for XDDC.eu search engine
*   Must implement:
*       - pluginname_show() -> Display forms
*       - pluginname_handle() -> Handle POST event, form input_names name must start with pluginname_
*/

    function xdcceu_show() {

	$examplestring = "Server: irc.myserver.com
Channel: #channel
Command: /msg Username xdcc send #1234
or command: /ctcp Username xdcc send #1234";

        echo ('
        <hr><h1>
        Paste from <a href="https://www.xdcc.eu" target="_blank">xdcc.eu</a>:</h1>
			<form method="POST">
				<textarea name="xdcceu_text" placeholder="'.$examplestring.'"></textarea><br>
				<input type="submit" value="Apply">
			</form>'
    	);	
    }
 
    function xdcceu_handle() {
    
        $xdcceu_text = $_POST['xdcceu_text'];
/*
         preg_match(
    string $pattern,
    string $subject,
    array &$matches = null,
    int $flags = 0,
    int $offset = 0
): int|false

Server: ([A-z.0-9-]+)
Channel: #([A-z.0-9-]+)
Command: \/msg ([A-z.0-9-]+)
Command: \/msg ([A-z.0-9-]+) xdcc send #([0-9]+)


*/

        $matches = array();
        if (preg_match("/Server: ([A-z.0-9-]+)/",$xdcceu_text, $matches)) {
            $server = $matches[1];
        }        
        $matches = array();
        if (preg_match("/Channel: #([A-z.0-9-]+)/",$xdcceu_text, $matches)) {
            $channel = '#'.$matches[1];
        }
        $matches = array();
        if (preg_match("/Command: \/msg ([^\s]+)/",$xdcceu_text, $matches)) {
            $user = $matches[1];
        }
        $matches = array();
        if (preg_match("/xdcc send #([0-9]+)/",$xdcceu_text, $matches)) {
            $pack = $matches[1];
        }

        $_GET['server'] = $server;
        $_GET['port'] = 6667;
        $_GET['channel'] = $channel;
        $_GET['user'] = $user;
        $_GET['pack'] = $pack;                                
    }     
    
?>


