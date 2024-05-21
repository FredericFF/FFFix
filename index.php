<?php
    // Report all PHP errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);     
    
    // Fetch plugins
    $plugins = array();
    $handler = opendir('plugins');                       
    while ($filename = readdir($handler)) {
        if (pathinfo($filename, PATHINFO_EXTENSION) == "php") {                						 
            $plugin = array();
            $plugin['name'] = pathinfo($filename, PATHINFO_FILENAME);
            $plugin['file'] = pathinfo($filename, PATHINFO_BASENAME);
            $plugins[] = $plugin;
            include('plugins/'.$plugin['file']);
        }
    } 
    
    // Handle plugins POST
    foreach ($_POST as $key => $value) {           
        foreach ($plugins as $plugin) {
            if (strpos($key, $plugin['name']) !== false) {
                $handle_function = $plugin['name'].'_handle';
                $handle_function();            
            }
        }       
    }  
    
?>
<!DOCTYPE html>
<html>
	<head>
		<title>FFFix</title>
		<link rel="stylesheet" href="web/main.css">
	</head>
	<body class="omgcontainer centered">
        <div class="topbox">
            <div class="logo" id="logo" >
                <a href="index.php"><img src="web/fffix.png" alt="FFFix Fetch Files From Irc Xdcc""></a>
            </div>
            <div class="main_container">
            	<div class="page_header">
            		<h1 class="omgcenter">
				<a class="title" style="text-decoration: none;" href="index.php">
					FFFix Fetch Files From Irc Xdcc
				</a>
			</h1>
            	</div>
	            <div id="items_table_div">
	                <table align="center">
		                <tr>
			                <th>#</th>
			                <th>Channel</th>
			                <th>Bot</th>
			                <th>Pack</th>
			                <th>Status</th>
			                <th>Filename</th>
			                <th>Size</th>
			                <th>%</th>
			                <th>Log</th>
			                <th>Action</th>
		                </tr>
		                <tbody id="items_table">
            				<tr>
                				<td>
                					<div id="ClientList" class="ClientList">Loading item list...</div>
                				</td>
                			</tr>
            			</tbody>
	                </table>
                </div>
            </div>
        </div>
	    <section>
			<form method="GET" action="client_control/start.php" class="omgvertical">
				<input type="text" name="server" placeholder="IRC server address:" value="<?php echo showContent("server"); ?>">
				<input type="number" name="port" placeholder="IRC server port:" min="0" value="<?php echo showContent("port","6667"); ?>">
				<input type="text" name="channel" placeholder="IRC channel:" value="<?php echo showContent("channel"); ?>">
				<input type="text" name="user" placeholder="User nick:" value="<?php echo showContent("user"); ?>">
				<input type="number" name="pack" placeholder="Pack number:" min="-1" value="<?php echo showContent("pack"); ?>">
				<input type="submit" value="GO GET IT! &raquo;">
			</form>
		</section>
	    <section>
        <?php                 
            foreach($plugins as $plugin) {                
                $show_function = $plugin['name'].'_show';
                $show_function();
            }
        ?>	    	    
		</section>
        <br>
		<section>
			<div id="notifCenter" class="notification">
				<h3 class="omgcenter">Notification</h3>
					<p id="notif">
					<?php
					if (isset($_GET["clientstarted"]) && $_GET["clientstarted"] == "yes") {
						echo "Client started! ";
					}
					elseif (isset($_GET["clientstopped"]) && $_GET["clientstopped"] == "yes") {
						echo "Client stopped! ";
					}
					?>
				</p>
			</div>
		</section>

		<?php
include 'web/footer.php';

function showContent($field, $default = "") {
	if (isset($_GET[$field]) && $_GET[$field] != "") {
				return $_GET[$field];
	}
	else {
		return $default;
	}
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

	<script type='text/javascript'>
	
        function bytesForHuman(bytes, decimals = 2) {
        
            if (bytes === undefined) {
                return('');
            } if (bytes == 0) {
                return('0');
            }
        
            let units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB']
            let i = 0
            for (i; bytes > 1024; i++) {
                bytes /= 1024;
            }
            return parseFloat(bytes.toFixed(decimals)) + ' ' + units[i]
        }
        
        function addRow(tableID, cells_contents) {
            // Get a reference to the table
            let tableRef = document.getElementById(tableID);

            // Insert a row at the end of the table
            let newRow = tableRef.insertRow(-1);

            cells_contents.forEach((item) => {
                // Insert a cell in the row at the end
                let newCell = newRow.insertCell(-1);
                // Append a text node to the cell
/*                let newText = document.createTextNode(item);                        
                newCell.appendChild(newText);*/
                newCell.innerHTML = item;
            });                                
        }

        AjaxGetRequest();
		setInterval(AjaxGetRequest, 3*1000 );//all 3 seconds

		function AjaxGetRequest() {
			var req = new XMLHttpRequest();

			req.open('GET', 'ajax/clients_list.php', true); //true for asynchronous

			req.onreadystatechange = function () {
				if (req.readyState == 4) { //4 == XMLHttpRequest.DONE ie8+
					if((req.status == 200) || (req.status == 304)) {
						var obj = JSON.parse(req.responseText);
						ClientList(obj);
					}
					else {
					}
				}
			};
			req.send(null);
		}

		function ClientList(clients) {

			document.getElementById('notif').innerHTML='';
			document.getElementById('notifCenter').style.visibility="hidden";
     	         	    
     	    document.getElementById("items_table").innerHTML = "";
     	         	    
			for (counter = 0; counter < clients.length; ++counter) {							
			
                item = clients[counter]; 
			               
                if (item.completion === undefined) {
                    item.completion = "0";
                }
                
                if (item.last_active_time === undefined) {
                    var time = "?";
                } else {

                    item.last_active_time;                    
                    var now = Date.now()/1000; // UNIX
                    
                    if (now-item.last_active_time > 120) { 
                        item.status = item.status + ' (stale)';
                    }
                }
                               
     	        addRow(
                    'items_table',
                    [
                        counter+1,
                        item.channel,
                        item.user,
                        item.pack,
                        item.status,
                        item.filename,
                        bytesForHuman(item.filesize),
                        parseFloat(item.completion).toFixed(2)+'%',
                        '<a href="functions/log_show.php?log=' + item.nick + '.log" target="_blank">View Log</a>',
                        '<a href="client_control/stop.php?nick=' + item.nick + '">Stop</a>'+'<a href="client_control/restart.php?nick=' + item.nick + '">Restart</a>' + '<a href="client_control/delete.php?nick=' + item.nick + '">Delete</a>'
                    ]
                );
                
			}
			
			if (counter = 0) {
			    addRow('items_table',['No items']);
			}
			
		}
	</script>
</body>
</html>
