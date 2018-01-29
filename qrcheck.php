<?php
	session_start();
/*** [QR-Code Authentifizierung] 
****
****
****
****
****/
	
	require_once ('_core/templatesystem-min.php');
	require_once ('_core/.function.php');
	require_once ('_core/plugins/phpmailer/PHPMailerAutoload.php');
	
	$current_sessionid = session_id();
	
	// ## Datenbankverbindung open ##
	$db = new db($db_host, $db_user, $db_pass, $db_name);
	$tpl = new Template();
	
	function add_logfile($f_db, $f_task,$f_note){
		$currentAgent =$_SESSION['ciruserUid'];
		$currentTimestamp = date("Y-m-d H:i:s");
		$query = "INSERT INTO logfile (uid, task, note, timestamp) VALUES ('$currentAgent', '$f_task', '$f_note', '$currentTimestamp')";
		$result = $f_db->query($query);
	}
	
	// LOGIN
	/* Chk1: Prüfen ob alles gesendet wurde per Formular */
	if (isset($_POST["login_agentname"]) AND isset($_POST["login_password"]) AND $_GET["action"]=="login") { // <Chk1: JA>
		$check_agentname = str_replace(' ','',$_POST ["login_agentname"]);
		$query = sprintf("SELECT uid, agentname, password FROM cirs_users WHERE agentname = '%s'", $db->real_escape_string($check_agentname));
		$result = $db->query($query);
		$row=$result->fetch_array(MYSQLI_ASSOC);
		/* Chk2: Prüfen Ob Agent vorhanden ist */
		if (isset($row['password'])) { // <Chk2: Ja>
			/* Chk3: Prüfen ob Passwort richtig ist */
			if (md5($_POST["login_password"])==$row['password']) {  // <Chk3: Ja>
				$_SESSION['ciruserName'] = $row["agentname"];
				$_SESSION['ciruserUid']=$row['uid'];
				$sql_currenttime=time();
				$query = sprintf("UPDATE cirs_users SET sid = '$current_sessionid', timestamp = '$sql_currenttime' WHERE uid = '%s'", $db->real_escape_string($row['uid']));
				$result = $db->query($query);
			} else { // <Chk3: NEIN>
				$tpl->assign ("login_error",notifybox("danger","Please check your Password!!!!"));
			}
		} else { // <Chk2: NEIN>
			$tpl->assign ("login_error",notifybox("danger","Please check your Agentname, no match with our database..."));
		}
	}

	
	
	if ($_GET["action"]=="newpwd"){
			If ($_POST["btn_action"]=="sav"){
				// Änderungen speichern
				if ($_POST['password']==$_POST['password_confirm']){
					$sql_password=md5($_POST['password']);
					$sql_ciruser= $_SESSION['agent'];
					$query = sprintf("UPDATE cirs_users SET  password = '$sql_password' WHERE uid = '%s'", $db->real_escape_string($_SESSION["ciruserUid"]));
					$result = $db->query($query);
					//Logfile erstellen
					add_logfile($db, "ciruser password change","user: $sql_ciruser");
					$err_mode = "success";
					$err_msg="Data has been successfully changed! [$sql_ciruser]";
				} else {
					$err_mode = "warning";
					$err_msg="Passwort not match! ";
				}
				
			}
		}






	// LOGOUT
	if ($_GET["action"]=="logout"){
		goto logout;
	}
	
	
	// Prüfen ob der aktuelle Session_ID einen angemeldeten User zugewiesen ist
	$query = "SELECT timestamp FROM cirs_users WHERE sid = '$current_sessionid'";
	$result = $db->query($query);
	$row=$result->fetch_array(MYSQLI_ASSOC);

	//Prüfen ob die Zeit abgelaufen ist
	if (isset($row['timestamp'])) {
		if ($row['timestamp']+$set_ttl>=(time())){
			$userloggedout = false;
		} else {
			logout:
			$query = "UPDATE cirs_users SET sid = NULL WHERE sid = '$current_sessionid'";
			$result = $db->query($query);
			session_destroy();
			session_unset();
			$_SESSION['ciruserName']="";
			$_SESSION['ciruserUid']=0;
			$userloggedout = true;
		}
	} else {
		$userloggedout = true;
	}

	// Pürfen ob ein User angemeldet ist
	if ($userloggedout) {
		// <Kein User angemeldet>
		$tpl->load("template/qrcheck/tpl_login.html");
		$tpl->assign ("login_error","");
		$tpl->assign ("SessionID",$current_sessionid);
		echo $tpl->display();
	} else {
		$tpl->assign ("qratuh_error","");	
		$tpl->assign ("cirusername",$_SESSION['ciruserName']);
		$tpl->assign ("ciruserid",$_SESSION['ciruserUid']);
		//  Chk1: Prüfen ob ein Authcode übergeben wurden
		if (isset($_GET["authcode"])){ // <Chk1: JA>
			// Chk2: Prüfen ob ein AuthCode in der Datenbank vorhanden ist...
			$query = sprintf("SELECT id, agentname, faction, authcode, md_ciruser, md_check FROM agents WHERE authcode LIKE '%s'", $db->real_escape_string($_GET["authcode"])); 
			$result = $db->query($query);
			$row=$result->fetch_array(MYSQLI_ASSOC);
			if (isset($row['id'])) { // <Chk2: JA>
				// Chk3: Prüfen ob ein Confirm gesendet wurde
				if ($_POST['btn_action']=="confirm"){ // <Chk3: JA - es wurden Daten gesendet>
					$sql_ciruseruid = $_SESSION['ciruserUid'];
					$sql_mdcheck = $_POST['missioncomplete'];
					$sql_mdtime = date("Y-m-d H:i:s");
					$sql_agentid = $row['id'];
					$query = sprintf("UPDATE agents SET md_ciruser = '$sql_ciruseruid', md_check = '$sql_mdcheck', md_time= '$sql_mdtime' WHERE id = '%s'", $db->real_escape_string($sql_agentid));
					$result = $db->query($query);
					add_logfile($db, "ciruser AgentConfirm","AgentID: $sql_agentid | Mission: $sql_mdcheck |");
					$tpl->assign ("qratuh_error",notifybox("success","Agent ".$row['agentname']." completed $sql_mdcheck Mission."));
					goto index_load;
				}
				if ($row['faction']=='') { $tpl_faction = "ohne" ;} else { $tpl_faction = $row['faction'];}
				$tpl->load("template/qrcheck/tpl_confirm-mission.html");
				$tpl->assign ("login_error","");
				$tpl->assign ("agentFaction",$tpl_faction);
				$tpl->assign ("agentName",$row['agentname']);
				$tpl->assign ("agentAuthcode",$row['authcode']);
				if ($row['md_check']>=12) {
					$tpl->assign ("missionday_state",'<span class="glyphicon glyphicon-ok-circle" aria-hidden="true" style="font-size: 48pt; color: green;"></span>');
				} else {
					$tpl->assign ("missionday_state",'<span class="glyphicon glyphicon-remove-circle" aria-hidden="true" style="font-size: 48pt; color: red;"></span>');
				}
				// Chk4: Prüfen ob bereits ein CIR User Mission bestätigt hat
				if ($row['md_ciruser']==0 OR $row['md_check']<12) { // <Chk4: NEIN>
					for ($i =0; $i <= 24; $i++) {
						if ($row['md_check']==$i){$md_check=" selected";} else {$md_check="";}
						$tpl_missioncomplete.= '
												<option value="' . $i . '"' . $md_check . '>' . $i . '</option>';
					}
					$tpl->assign ("agentconfirm_error","");
					$tpl->assign ("select_missioncomplete", $tpl_missioncomplete);
				} else {
					$tpl->assign ("agentconfirm_error",notifybox("danger","This Agent allready completed more than 12 Mission."));
					$tpl->assign ("missionconfirm_form",'style="display: none;"');
				}


			} else  { // <Chk2: NEIN>
				$tpl->assign ("qratuh_error",notifybox("warning","This is not a vaild QR-Code!"));
				goto index_load;
			}
		} else { // <Chk1: NEIN>
			index_load:
			$tpl->load("template/qrcheck/tpl_index.html");
		}
		echo $tpl->display();
	}
		
	$db-> close;
?>