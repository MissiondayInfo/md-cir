<?php
	class Template {
		/* Platzhalter */
		private $leftDelimiter = '{$';				/* Der linke Delimter für einen Standard-Platzhalter.*/
		private $rightDelimiter = '}';				/* Der rechte Delimter für einen Standard-Platzhalter.*/
		
		/* Variablen für das System (Prüfen ob nötig)*/
		private $templateFile = "";					/* Der Dateiname der Templatedatei.*/
		private $templatePath = "";					/* Der komplette Pfad der Templatedatei.*/
		//private $languageFile = "";					/* Der Dateiname der Templatedatei.*/
		private $template = "";							/* Der Inhalt des Templates.*/



		/* Eine Templatedatei öffnen.	*/
		public function load($f_file)    {
			$this->template = file_get_contents($f_file);
		}

		/* Zuordnungen zuweisen für Standard-Platzhalter (in Array "assignVar" speichern)*/
		public function assign($f_replace, $f_replacement) {
			//$this->assignVar[$f_replace] = $f_replacement;
			$this->assignVar[$f_replace] .= $f_replacement;
		}
	
		/* Einen Standard-Platzhalter ersetzen. */
		private function replaceAssignVars($f_assign) {
			foreach ($f_assign as $f_replace => $f_replacement) {
				$this->template = str_replace( $this->leftDelimiter.$f_replace.$this->rightDelimiter, $f_replacement, $this->template );
			}
		}
		
		/* Alles Parsen und entsprechend ersetzen */
		private function parsen() {
			// Standardvariablem parsen
		  $this->replaceAssignVars($this->assignVar);
			
	  }
	 
		/* Das "fertige Template" ausgeben.	*/
		public function display() {
			$this->parsen();
			return $this->template;
		}
	}	
?>