<?php
	// Datenbankeinstellungen
	$db_host = "localhost";	#Hostname
	$db_name = "db_name";						#Datenbankname
	$db_user = "db_username"; 						#Datenbankuser
	$db_pass = "db_password"; 					#Passwort
	date_default_timezone_set("Europe/Berlin");

//b1767_ingress_Dummy	
#
	$set_ttl = 60*15;
	
	// ### Datenbank Klassen initalisieren ###
	class db extends mysqli {
		public function __construct ($db_host, $db_user, $db_pass, $db_name) {
			parent::__construct ($db_host, $db_user, $db_pass, $db_name);
		}
	}
	
	function send_qrcode($f_email,$f_agentname,$f_authcode,$f_qrurl){
		$timestamp = time();
		$datum = date("d.m.Y",$timestamp);
		$uhrzeit = date("H:i",$timestamp);

		
		$tpl = new Template();
		$tpl->load("template/mail_missionday.html");
		$tpl->assign ("f_agentname", $f_agentname);
		$tpl->assign ("f_email", $f_email);
		$tpl->assign ("f_authcode", $f_authcode);
		$tpl->assign ("f_qrurl", $f_qrurl);
		$tpl->assign ("datum", $datum);
		$tpl->assign ("uhrzeit", $uhrzeit);
		$email_message = 	$tpl->display();

		//Create a new PHPMailer instance
		$mail = new PHPMailer;


		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = 0;
		$mail->Debugoutput = 'html';																				//Ask for HTML-friendly debug output

 

		$mail->isSMTP();                                      	// Set mailer to use SMTP
		$mail->Host = 'mail.server.com';  							// Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               	// Enable SMTP authentication
		$mail->Username = 'email@missionday.info';            // SMTP username
		$mail->Password = 'mail_password';                           		// SMTP password
		$mail->SMTPSecure = 'tls';                            	// Enable TLS encryption, `ssl` also accepted
		$mail->Port = 587;                                    	// TCP port to connect to
																				//Password to use for SMTP authentication
		$mail->setFrom('mail@missionday.info', '[ORGA] Mission Day Dummy');			//Set who the message is to be sent from
		$mail->addReplyTo('noreply@missionday.info', '[ORGA] Mission Day Dummy');		//Set an alternative reply-to address
		$mail->addAddress($f_email, $f_agentname);															//Set who the message is to be sent to
		$mail->Subject = 'Thanks for Check-In-Registration to Mission Day Dummy';				//Set the subject line
		/*$mail->msgHTML(file_get_contents('missionday.html'), dirname(__FILE__));					//Set Message from a files*/
		$mail->MsgHTML($email_message);																			//Set Message from a variable
		$mail->AltBody = 'This is a plain-text message body';												//Replace the plain text body with one created manually
		/*$mail->addAttachment('images/phpmailer_mini.png');												//Attach an image file*/


		$mail->send();
	}	
	
	function notifybox($f_mode,$f_message) {
		/**** Erkl?rung mode
		 ****
		 **** default		Standard		Grau
		 **** primary		Standard		Blau
		 **** success		Erfolgreich	Gr?n
		 **** info			Information	Hellblau
		 **** warning		Warnung		Gelb
		 **** danger		Gefahr		Rot
		 ****/
		switch ($f_mode) { //Aktion auswerten
			case "primary":
				$notify_class="primary";
				$notify_title="Message";
				break;
			case "success":
				$notify_class="success";
				$notify_title="Success";
				break;
			case "info":
				$notify_class="info";
				$notify_title="Information";
				break;
			case "warning":
				$notify_class="warning";
				$notify_title="Warning";
				break;
			case "danger":
				$notify_class="danger";
				$notify_title="Attention";
				break;
			default:
			$notify_class="default";
			$notify_title="Message";
		}
		
		$notify_msg=$f_message;
		return '
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12">
				<div class="alert alert-'.$notify_class.' alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<strong>'.$notify_title.'!</strong> '.$notify_msg.'
				</div>
			</div>
		</div>';
	}
	
	function authcodegenerator($f_authcodelaenge,$f_authcodestaerke) {
		mt_srand((double) microtime() * 1000000); // Zufallsgenerator starten
		if ($f_authcodestaerke >= 1) {
			$authcodeset = "abcdefghijklmnopqrstuvxyz";
		}
		if ($f_authcodestaerke >= 2) {
			$authcodeset .= "ABCDEFGHIKLMNPQRSTUVWXYZ";
		}
		if ($f_authcodestaerke >= 3) {
			$authcodeset .= "123456789";
		}
		if ($f_authcodestaerke >= 4) {
			$authcodeset .= "!§$%&/()=";
		}
		for ($n=1;$n<=$f_authcodelaenge;$n++)
			$authcode .= $authcodeset[mt_rand(0,(strlen($authcodeset)-1))];	
		return ($authcode);
	}
	
	function leftDay($begin,$end,$format,$sep){ 
		$pos_d = strpos($format, 'd'); 
		$pos_m = strpos($format, 'm'); 
		$pos_y = strpos($format, 'Y'); 

		$begin = explode($sep,$begin); 
		$end = explode($sep,$end); 

		$first = GregorianToJD($end[$pos_m],$end[$pos_d],$end[$pos_y]); 
		$second = GregorianToJD($begin[$pos_m],$begin[$pos_d],$begin[$pos_y]); 

		if($first > $second) 
			return $first - $second; 
		else 
			return $second - $first; 
		}
		
	function timestampTOdatetime ($f_timestamp=0){
		$date = new DateTime();
		$date->setTimestamp($f_timestamp);
		return $date->format('Y-m-d H:i:s');
	}
?>
