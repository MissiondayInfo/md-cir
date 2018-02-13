<?php
//error_reporting(E_ALL);
	/*
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

	require_once ('_core/templatesystem-min.php');
	require_once ('_core/.function.php');
	require_once ('_core/plugins/phpmailer/PHPMailerAutoload.php');

	// ## Datenbankverbindung open ##
	$db = new db($db_host, $db_user, $db_pass, $db_name);

	
	// SPAM SCHUTZ EINFÜGEN PER IP
	
	
	if (isset($_POST["agent"]) AND isset($_POST["email"])) { /*Prüfen ob alles gesendet wurde per Formular */
		$agentname = str_replace(' ','',$_POST ["agent"]);

		$query = sprintf("SELECT email, authcode FROM agents WHERE agentname = '%s'", $db->real_escape_string($agentname));
		$result = $db->query($query);
		$row=$result->fetch_array(MYSQLI_ASSOC);

		// Prüfen Ob Agent bereits vorhanden:
		if (!isset($row['email'])) {
			// <NEIN> DB Eintrag anlegen
			$tgname = $_POST ["tgname"];
			$faction = $_POST ["faction"];
			$email = $_POST["email"];
			$authcode = authcodegenerator(8,3);
			$reg_time = date("Y-m-d H:i:s");
			$qr_url = 'https%3A%2F%2Fdummy.missionday.info%2Fqrcheck.php%3F%26authcode%3D'.$authcode;
			
			$query = "INSERT INTO agents (agentname, tgname, faction, email, authcode, reg_time) VALUES ('$agentname', '$tgname', '$faction', '$email', '$authcode', '$reg_time')";
			$result = $db->query($query);
	
			//Mail senden
			send_qrcode($email,$agentname,$tgname,$authcode, $qr_url);

			include ("template/tpl_qrcode.html");
		} else {
			// <JA> --> Prüfen ob die E-Mail-Adresse stimmt:
			if ($_POST["email"]==$row['email']) {
				// <JA>
				$errmsg = "You're already registered!";
				$authcode = $row['authcode'];
				$qr_url = 'https%3A%2F%2Fdummy.missionday.info%2Fqrcheck.php%3F%26authcode%3D'.$authcode;
				include ("template/tpl_qrcode.html");
			} else {
				// <NEIN>
				$errmsg = "Please check your email address, no match with our database...";
				include ("template/tpl_index.html");
			}
		}
	} else {
		include ("template/tpl_index.html");
	}
	$db-> close();
?>

