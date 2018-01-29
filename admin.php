<?php

	session_start();
/*** [Admin-System] 
****
**** Variabeln ändern.... Sinnvolle Namen


	-> Prüfen ob AgentName in der Datenbank ist
		|
		\_ {NEIN} -> Daten in der Datenbank eintragen
		|			 -> E-Mail mit Infos senden
		|			 -> QR-Code Seite mit AuthCode anzeigen	
		|
		\_ {JA} -> Prüfen ob E-Mail Adresse gleich ist
					  |
					  \_ {JA}   -> QR-Code Seite mit AuthCode anzeigen
					  |			-> Mit Hinweis das man bereits angemeldet ist
					  |
					  \_ {NEIN} -> Info ausgeben das die E-Mail Adresse eine Andere ist. Prüfen und ggf. beim Support melden

	  GET Option
	
	*/

	//Überprüfungen der Dateneingaben durchführen!!!!
	// ALLES Sauber überprüfen und ordentlich machen ...
	
	require_once ('_core/templatesystem-min.php');
	require_once ('_core/.function.php');
	require_once ('_core/plugins/phpmailer/PHPMailerAutoload.php');
	// GGF Template System nutzen
	
	
	// ## Datenbankverbindung open ##
	$db = new db($db_host, $db_user, $db_pass, $db_name);

	function add_logfile($f_db, $f_task,$f_note){
		$currentAgent =$_SESSION['agent_uid'];
		$currentTimestamp = date("Y-m-d H:i:s");
		$query = "INSERT INTO logfile (uid, task, note, timestamp) VALUES ('$currentAgent', '$f_task', '$f_note', '$currentTimestamp')";
		$result = $f_db->query($query);
	}
	// logout
	if ($_GET['action']=="logout"){
		session_destroy();
		session_unset();
		$_SESSION['agent']="";
		$_SESSION['agent_uid']=0;
	}
	
	// login
	if (isset($_POST["login_agent"]) AND isset($_POST["login_password"]) AND $_GET["action"]!="logout") { /* Prüfen ob alles gesendet wurde per Formular */
		$agentname = str_replace(' ','',$_POST ["login_agent"]);
		$query = sprintf("SELECT uid, agentname, password, gid FROM cirs_users WHERE agentname = '%s'", $db->real_escape_string($agentname));
		$result = $db->query($query);
		$row=$result->fetch_array(MYSQLI_ASSOC);
		/* Prüfen Ob Agent vorhanden ist */
		if (isset($row['password'])) { // <Ja>
			/* Prüfen ob Passwort richtig ist */
			if (md5($_POST["login_password"])==$row['password']) {  // <Ja>
				if ($row[gid]!=1){
					$err_mode = "danger";
					$err_msg = "You are not permited to visit this site!";
				} else {
					$_SESSION['agent'] = $_POST["login_agent"];
					$_SESSION['agent_uid']=$row['uid'];
				}				
			} else { // <NEIN>
				$err_mode = "warning";
				$err_msg = "Please check your Passwort!!!!";
			}
		} else { // <NEIN>
			$err_mode = "warning";
			$err_msg = "Please check your Agentname, no match with our database...";
		}
	}
	
	
	if ($_SESSION['agent']!=""){
		// Agent ist angemeldet
		
		switch (htmlentities($_GET["action"])) { //Aktion auswerten
		case "ciruseredit":
			if (isset($_GET["id"])){
				$query = sprintf("SELECT uid,agentname,password,gid,timestamp FROM cirs_users WHERE uid LIKE '%s'", $db->real_escape_string($_GET["id"])); // GGf. Prüfen ob ID vorhanden
				$result = $db->query($query);
				$row=$result->fetch_array(MYSQLI_ASSOC);
				$ciruserName=$row['agentname'];
				$ciruserPassword=$row['password'];
				$ciruserGid=$row['gid'];
				$ciruserTimestamp=$row['timestamp'];
				include ("template/admin/tpl_ciruseredit.html");
				break;
			}
			If ($_POST["btn_action"]=="sav"){
				// Änderungen speichern
				$logfiletext = "user: $sql_agentname | ";
				if ($_POST['agentname']!=$_POST['old_agentname']){ 
					// TODO: Überprüfen ob Benutzername existiert
					$logfiletext .= "Agentname -> ".$_POST['agentname']. " | "; 
				}
				$sql_agentname=$_POST['agentname'];
				if (md5($_POST['password'])==$_POST['old_password']){
					$sql_password=$_POST['old_password'];
				} else {
					// TODO: Prüfen ob Passwort stimmt
					$sql_password=md5($_POST['password']);
					$logfiletext .= "passwort -> newHASH | ";
				}
				if ($_POST['gid']!=$_POST['old_gid']){ $logfiletext .= "GID -> ".$_POST['gid']. " | "; }
				$sql_gid=$_POST['gid'];
				$query = sprintf("UPDATE cirs_users SET agentname = '$sql_agentname', password = '$sql_password', gid = '$sql_gid' WHERE uid = '%s'", $db->real_escape_string($_POST["agentid"]));
				$result = $db->query($query);
				//Logfile erstellen
				add_logfile($db, "ciruser edit","change: $logfiletext");
				$err_mode = "success";
				$err_msg="Data has been successfully changed! [$sql_agentname]";
				// goto defaultpage;
				break;
			}
			If ($_POST["btn_action"]=="del"){
				$query = sprintf("DELETE FROM cirs_users WHERE uid = '%s'", $db->real_escape_string($_POST["agentid"]));
				$result = $db->query($query);
				$sql_agentname=$_POST['agentname'];
				add_logfile($db, "ciruser edit","delete: $sql_agentname");
				$err_mode = "success";
				$err_msg="User has been successfully delete! [$sql_agentname]";
				// goto defaultpage;
				break;
			}
			// goto defaultpage;
			
			
		case "ciruserpaswd":
			If ($_POST["btn_action"]=="sav"){
				// Änderungen speichern
				if ($_POST['password']==$_POST['password_confirm']){
					$sql_password=md5($_POST['password']);
					$sql_ciruser= $_SESSION['agent'];
					$query = sprintf("UPDATE cirs_users SET  password = '$sql_password' WHERE uid = '%s'", $db->real_escape_string($_POST["agentid"]));
					$result = $db->query($query);
					//Logfile erstellen
					add_logfile($db, "ciruser password change","user: $sql_ciruser");
					$err_mode = "success";
					$err_msg="Data has been successfully changed! [$sql_ciruser]";
				} else {
					$err_mode = "warning";
					$err_msg="Passwort not match! ";
				}
				// goto defaultpage;
				break;
			}
			If ($_POST["btn_action"]=="del"){
				$query = sprintf("DELETE FROM cirs_users WHERE uid = '%s'", $db->real_escape_string($_POST["agentid"]));
				$result = $db->query($query);
				$sql_agentname=$_POST['agentname'];
				add_logfile($db, "ciruser edit","delete: $sql_agentname");
				$err_mode = "success";
				$err_msg="User has been successfully delete! [$sql_agentname]";
				// goto defaultpage;
				break;
			}
			// goto defaultpage;			
			
			
		case "cirusernew":
			if (isset($_POST["btn_action"])){
				$query = sprintf("SELECT COUNT(agentname) AS anzahlagentname FROM cirs_users WHERE agentname= '%s'", $db->real_escape_string($_POST['agentname']));
				$result = $db->query($query);
				$row=$result->fetch_array(MYSQLI_ASSOC);
				// Prüfen Ob Agent bereits vorhanden:
				if ($row['anzahlagentname']==0) {
				// <NEIN>
					if ($_POST['password']==$_POST['password_confirm']){
						// Anlegen
						$sql_agentname=$_POST['agentname'];
						$sql_password=md5($_POST['password']);
						$sql_gid=$_POST['gid'];
						$query = "INSERT INTO cirs_users (agentname, password, gid) VALUES ('$sql_agentname', '$sql_password', '$sql_gid')";
						$result = $db->query($query);
						$err_mode = "success";
						$err_msg = "Add new User!";
						add_logfile($db, "ciruser new","user: $sql_agentname |");
						// goto defaultpage;
						break;
					} else {
						$err_mode = "warning";
						$err_msg ="Password not match!";
						include ("template/admin/tpl_cirusernew.html");
						break;
					}
				 }else {
					$err_mode = "warning";
					$err_msg ="User allready exist!";
					include ("template/admin/tpl_cirusernew.html");
					break;
				}
			} else {
				include ("template/admin/tpl_cirusernew.html");
				break;
			}
		case"ciruserforcelogout":
			if (isset($_GET["id"])){
				$sql_id =  $_GET['id'];
				$query = sprintf("UPDATE cirs_users SET sid = NULL WHERE uid = '%s'", $db->real_escape_string($sql_id));
				$result = $db->query($query);
				add_logfile($db, "ciruser forcelogot","uid: $sql_id |");
				$err_mode = "success";
				$err_msg="User has been successfully logged out! [$sql_id]";
				// goto defaultpage;
			}
		case "agentedit":
			if (isset($_GET["id"])){
				$query = sprintf("SELECT id,agentname,faction,email,authcode, reg_time, edit_time FROM agents WHERE id LIKE '%s'", $db->real_escape_string($_GET["id"])); // GGf. Prüfen ob ID vorhanden
				$result = $db->query($query);
				$row=$result->fetch_array(MYSQLI_ASSOC);
				$agentName=$row['agentname'];
				$agentEmail=$row['email'];
				$agentFaction=$row['faction'];
				$agentRegtime=$row['reg_time'];
				$agentEdittime=$row['edit_time'];
				$authcode=$row['authcode'];
				$qr_url = "https%3A%2F%2Fdummy.missionday.info%2Fqrcheck.php%3F%26authcode%3D".$authcode;
				include ("template/admin/tpl_agentedit.html");
				break;
			}
			If ($_POST["btn_action"]=="sav"){
				// Änderungen speichern
				$sql_agentname = $_POST["agentname"];
				$sql_email = $_POST["email"];
				$sql_faction = $_POST["faction"];
				$sql_edittime = date("Y-m-d H:i:s");
				$query = sprintf("UPDATE agents SET agentname = '$sql_agentname', email = '$sql_email', faction = '$sql_faction', edit_time = '$sql_edittime'  WHERE id = '%s'", $db->real_escape_string($_POST["agentid"]));
				$result = $db->query($query);
				//Logfile erstellen
				add_logfile($db, "agent edit","change: $sql_agentname |");
				$err_mode = "success";
				$err_msg="Data has been successfully changed! [$sql_agentname]";
				// goto defaultpage;
				break;
			}
			If ($_POST["btn_action"]=="del"){
				echo "LÖSCHEN";
				//MODAL UND LÖSCHEN
				// goto defaultpage;
				break;
			}
			// goto defaultpage;
		case "agentqrmail":
			if (isset($_GET["id"])){
				$query = sprintf("SELECT agentname,email,authcode FROM agents WHERE id LIKE '%s'", $db->real_escape_string($_GET["id"])); // GGf. Prüfen ob ID vorhanden
				$result = $db->query($query);
				$row=$result->fetch_array(MYSQLI_ASSOC);
				$agentName=$row['agentname'];
				$agentEmail=$row['email'];
				$agentAuthcode=$row['authcode'];
				include ("template/admin/tpl_agentqrmail.html");
				break;
			}
			If ($_POST["btn_action"]=="snd"){
				$agentname=$_POST['agentname'];
				$email = $_POST['email'];
				$authcode = $_POST['authcode'];
				$qr_url = 'https%3A%2F%2Fdummy.missionday.info%2Fqrcheck.php%3F%26authcode%3D'.$authcode;
				send_qrcode($email,$agentname,$authcode, $qr_url);
				add_logfile($db, "qrresend","to: $agentname |");
				// goto defaultpage;
				break;
			}
		
		default:
			//defaultpage:
			// CIR User auflisten
			$sql_ttl = $set_ttl-time();
			
			$query = "SELECT sid FROM cirs_users WHERE timestamp < $sql_ttl";
			$result = $db->query($query);
			//	print_r($result);
			while($row=$result->fetch_array(MYSQLI_ASSOC)){
				$sql_sessionid = $row['sid'];
				echo $row['id'];
				//$query = "UPDATE cirs_users SET sid = NULL WHERE sid = '$sql_sessionid'";
			}
			
			$query = "SELECT uid, agentname, gid, sid, timestamp FROM cirs_users";
			$row = null; $result = null;
			$result2 = $db->query($query);
			//print_r($result);

			while($row=$result2->fetch_array(MYSQLI_ASSOC)){
			//	print_r($row);

				if ($row['gid']==1){
					$usergid='<span class="glyphicon glyphicon-user" aria-hidden="true" style="color: gray;" title="Administrator"></span>';
				} else{
					$usergid='<span class="glyphicon glyphicon-qrcode" aria-hidden="true" style="color: gray; title="QR-Code User"></span>';
				}

				if (isset($row['sid'])) {
					if ($row['timestamp']+$set_ttl>=time()){
						$useronline_state = 'style="color: #00FF00;" title="User currently Online!"';
					} else {
						$sql_sessionid=$row['sid'];
						//$query = "UPDATE cirs_users SET sid = NULL WHERE sid = '$sql_sessionid'";
						$result = $db->query($query);
						$useronline_state = 'style="color: gray" title="User Offline!"';
					}
				} else {
					$useronline_state = 'style="color: gray" title="User Offline!"';
				}
				$useronline='<span class="glyphicon glyphicon-off" aria-hidden="true"' . $useronline_state .'></span>';
				$datatable_ciruser .='
												<tr class="text-center">
													<td>'. $row['uid'] . '</td>
													<td>'. $row['agentname'] .'</td>
													<td>'.$usergid .' '.$useronline.'</td>
													<td>
														<a href="admin.php?action=ciruseredit&id='. $row['uid'] .'" type="button" class="btn btn-skin btn-sm">
															<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
														</a>
														<a href="admin.php?action=ciruserforcelogout&id='. $row['uid'] .'" type="button" class="btn btn-skin btn-sm" title="Force Logout">
															<span class="glyphicon glyphicon-log-out" aria-hidden="true"></span>
														</a>
													</td>
												</tr>
												';
				/**/

			}
			
			// Agent auflisten 
			
			$query = "SELECT id,agentname,faction FROM agents";
			$result = $db->query($query);
			while($row=$result->fetch_array(MYSQLI_ASSOC)){
				$datatable_agents .='
												<tr class="text-center">
													<td>'. $row['id'] . '</td>
													<td><span class="faction_'.$row['faction'].'">'. $row['agentname'] . '</span></td>
													<td>
														<a href="admin.php?action=agentedit&id='. $row['id'] .'" type="button" class="btn btn-skin btn-sm">
															<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
														</a>
														<a href="admin.php?action=agentqrmail&id='. $row['id'] .'" type="button" class="btn btn-skin btn-sm">
																<span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
														</button>
													</td>
												</tr>
												';
			}
			$query = "SELECT faction, COUNT(*) AS anzahl FROM agents GROUP BY faction";
			$result = $db->query($query);
			while($row=$result->fetch_array(MYSQLI_ASSOC)){
				$factionname = $row['faction'];
				$countfaction[$factionname] = $row['anzahl'];
				$countagentstotal=$countagentstotal+$row['anzahl'];
			}
			$percentfaction['']=round($countfaction['']/$countagentstotal*100,2);
			$percentfaction['enl']=round($countfaction['enl']/$countagentstotal*100,2);
			$percentfaction['res']=round($countfaction['res']/$countagentstotal*100,2);
			include ("template/admin/tpl_index.html"); 
		}
	} else {
		// Agent ist NICHT angemeldet
		include ("template/admin/tpl_login.html");
	}
	
	$db-> close();
?>
