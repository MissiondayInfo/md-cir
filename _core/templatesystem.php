<?php
	class Template {
		/* Setting */
		private $templateDir = "_core/templates/";		/* Der Ordner in dem sich die Templates befinden.*/
		private $languageDir = "_core/language/";			/* Der Ordner in dem sich die Sprach-Dateien befinden.*/
		
		/* Platzhalter */
		private $leftDelimiter = '{$';				/* Der linke Delimter für einen Standard-Platzhalter.*/
		private $rightDelimiter = '}';				/* Der rechte Delimter für einen Standard-Platzhalter.*/
		
		/* Sprachplatzhalter */
		private $leftDelimiterL = '\{L_';			/* Der linke Delimter für eine Sprachvariable (Sonderzeichen müssen escapt werden, weil der Delimter in einem regulärem Ausdruck verwendet wird.)*/
		private $rightDelimiterL = '\}';				/* Der rechte Delimter für eine Sprachvariable (Sonderzeichen müssen escapt werden, weil der Delimter in einem regulärem Ausdruck verwendet wird.)*/
		
		/* Funktion */
		private $leftDelimiterF = '{';				/* Der linke Delimter für eine Funktion.*/
		private $rightDelimiterF = '}';				/* Der rechte Delimter für eine Funktion.*/

		/* Kommentar */
		private $leftDelimiterC = '\{\*';			/* Der linke Delimter für ein Kommentar. (Sonderzeichen müssen escapt werden, weil der Delimter in einem regulärem Ausdruck verwendet wird.)*/
		private $rightDelimiterC = '\*\}';			/* Der rechte Delimter für ein Kommentar. (Sonderzeichen müssen escapt werden, weil der Delimter in einem regulärem Ausdruck verwendet wird.)*/
		
		/* Variablen für das System (Prüfen ob nötig)*/
		private $templateFile = "";					/* Der Dateiname der Templatedatei.*/
		private $templatePath = "";					/* Der komplette Pfad der Templatedatei.*/
		//private $languageFile = "";					/* Der Dateiname der Templatedatei.*/
		private $template = "";							/* Der Inhalt des Templates.*/


		/* Die Pfade festlegen. (Ordner kann per Konstrukt geladen werden, sonst werden die Ordner oben aus dem Setting genommen)	*/
		public function __construct($f_tpldir = "", $f_langdir = "") {
			// Template Ordner
			if ( !empty($f_tpldir) ) {
				$this->templateDir = $f_tpldir;
			}

			// Sprachdatei Ordner
			if ( !empty($f_langdir) ) {
				$this->languageDir = $f_langdir;
			}
		}

		/* Eine Templatedatei öffnen.	*/
		public function load($f_file)    {
			// Eigenschaften zuweisen
			$this->templateFile = $f_file;
			$this->templatePath = $this->templateDir.$f_file;

			// Wenn ein Dateiname übergeben wurde, versuchen, die Datei zu öffnen
			if( !empty($this->templatePath) ) {
				if( file_exists($this->templatePath) ) {
					$this->template = file_get_contents($this->templatePath);
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/* Zuordnungen zuweisen für Standard-Platzhalter (in Array "assignVar" speichern)*/
		public function assign($f_replace, $f_replacement) {
			//$this->assignVar[$f_replace] = $f_replacement;
			$this->assignVar[$f_replace] .= $f_replacement;
		}
		
		
		/* Standard-Platzhalte durch ganze Datei ersetzen */
		public function loadfile($f_replace, $f_file){
			if ( file_exists($f_file) ) {
				$this->template = str_replace( $this->leftDelimiter.$f_replace.$this->rightDelimiter, file_get_contents($f_file), $this->template );
			} else {
				return false;
			}
		}
	
		/* Die Sprachdateien öffnen und Sprachvariablem im Template ersetzen. */
		public function loadLanguage($f_files) {
			$this->languageFiles = $f_files;

			// Versuchen, alle Sprachdateien einzubinden
			for( $i = 0; $i < count( $this->languageFiles ); $i++ ) {
				if ( !file_exists( $this->languageDir .$this->languageFiles[$i] ) ) {
					return false;
				} else {
					include_once( $this->languageDir .$this->languageFiles[$i] );
					// Jetzt steht das Array $lang zur Verfügung
				}
			}
		  // Die Sprachvariablen der Klasse übergeben
		  $this->langVar = $lang;

		  // $lang zurückgeben, damit $lang auch im PHP-Code verwendet werden kann
		  return $lang;
		}

		/***preg_replace_callback ERKLÄRUMG***
			1. Argument der Such Patter (.*) gibt den jeweilgen Ausdruck für den array an
			2. Argument die Funktion die den array verarbeiten soll (Früher e)
			3. Der Text der Durchsucht werden soll
			
			
			Bsp: Text = Lade:wert|kosten
			1A: Lade:\(.*)|(.*)
					array[1] array[2]
         2A: array
			3A: Text
		*/
		
		// Ausgabe einer Datein als preg Ausdruck
		private function preg_file_get($f_matches){
			return file_get_contents($this->templateDir.$f_matches[1].'.'.$f_matches[2]);
		}
		
		/* Includes parsen und Kommentare aus dem Template entfernen. */
		private function parseFunctions() {
			// Includes ersetzen ( {include file="..."} )
			while( preg_match( "/" .$this->leftDelimiterF ."include file=\"(.*)\.(.*)\"".$this->rightDelimiterF ."/isUe", $this->template) )	{
				$this->template = preg_replace_callback( "/" .$this->leftDelimiterF ."include file=\"(.*)\.(.*)\"".$this->rightDelimiterF."/isU",array($this, 'preg_file_get'),$this->template );
			}
			
			// Kommentare löschen
			$this->template = preg_replace( '/' .$this->leftDelimiterC .'(.*)' .$this->rightDelimiterC .'/isU',"", $this->template );
		}
		
		// Ausgabe des Inahltes der Sprachdatei als preg Ausdruck
		private function preg_langVar($f_matches){
			return $this->langVar[strtolower($f_matches[1])];
		}
	  
		/* Sprachvariablen im Template ersetzen. */
		private function replaceLangVars() {
			//$this->template = preg_replace("/\{L_(.*)\}/isUe", "\$f_lang[strtolower('\\1')]", $this->template);
			//$this->template = preg_replace_callback("/\{L_(.*)\}/isU", "\$f_lang[strtolower('\\1')]", $this->template);
			$this->template = preg_replace_callback('/\{L_(.*)\}/isU', array($this, 'preg_langVar'), $this->template);
		}

		
		/* Einen Standard-Platzhalter ersetzen. */
		private function replaceAssignVars($f_assign) {
			foreach ($f_assign as $f_replace => $f_replacement) {
				$this->template = str_replace( $this->leftDelimiter.$f_replace.$this->rightDelimiter, $f_replacement, $this->template );
			}
		}
		
		/* Alles Parsen und entsprechend ersetzen */
	 	private function parsen() {
			// Funktionen parsen 
			$this->parseFunctions();

			// Standardvariablem parsen
		  $this->replaceAssignVars($this->assignVar);
			
		  // Sprachvariablen parsen
		  $this->replaceLangVars();
	  }
	 
	 
		/* Das "fertige Template" ausgeben.	*/
		public function display() {
			$this->parsen();
			echo $this->template;
		}
	 }
?>