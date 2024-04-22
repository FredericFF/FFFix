<!DOCTYPE html>
<html>
	<head>
		<title>FFFix</title>
		<link rel="stylesheet" href="main.css">
	</head>
	<body class="omgcontainer">
        <div class="topbox">
            <div class="logo" id="logo" >
                <a href="index.php"><img src="fffix.png" alt="FFFix Fetch Files From Irc Xdcc""></a>
            </div>
            <div class="main_container">
            	<div class="page_header">
            		<h1 class="omgcenter"><a class="title" href="index.php">FFFix Fetch Files From Irc Xdcc</a></h1>
            	</div>
	            <div id="items_table_div">
	                <h2>Items</h2>
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
			<form method="GET" action="startclient.php" class="omgvertical">
				<input type="text" name="server" placeholder="IRC server address:" value="<?php echo showContent("server"); ?>">
				<input type="number" name="port" placeholder="IRC server port:" min="0" value="<?php echo showContent("port","6667"); ?>">
				<input type="text" name="channel" placeholder="IRC channel:" value="<?php echo showContent("channel"); ?>">
				<input type="text" name="user" placeholder="User nick:" value="<?php echo showContent("user"); ?>">
				<input type="number" name="pack" placeholder="Pack number:" min="-1" value="<?php echo showContent("pack"); ?>">
				<input type="submit" value="GO GET IT! &raquo;">
			</form>
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
include 'footer.php';

include "config.php";

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

		setInterval(AjaxGetRequest, 5*1000 );//all 5 seconds

		function AjaxGetRequest() {
			var req = new XMLHttpRequest();

			req.open('GET', 'clientlist.php', true); //true for asynchronous

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
                    var time = Date(item.last_active_time).toString();
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
                        '<a href="showlog.php?log=' + item.nick + '.log" target="_blank">View Log</a>',
                        '<a href="stopclient.php?nick=' + item.nick + '">Stop</a>'+'<a href="restartclient.php?nick=' + item.nick + '">Restart</a>'
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
