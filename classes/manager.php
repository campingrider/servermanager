<?php
	namespace campingrider\servermanager;
	
	/** This class contains overall settings and controls the behavior of the system. 
	 * 
	 * @package servermanager
	 */	 
	class manager {
		
		/** @var string[] description for all settings which can be written out to an ini file */
		private static $settings_descriptions = array(
			'title'=>'The following phrase is shown as a title for the server manager',
			'banner_path'=>'The following path points to the banner image'
		);
		
		/** @var string[] contains default values for all settings */
		private $settings = array(
			'title'=>'Title not configured yet',
			'banner_path'=>'no_path.png'
		);
		
		/** Constructor.
		 * 
		 * @param string|string[] $settings settings to load, either directly via array or through the path of an ini file containing the information 
		 */
		public function __construct($settings) {
			
			$loaded = array();
			
			// load either from file or from array
			if (is_string($settings)) {
				if (is_file($settings)) {
					$loaded = parse_ini_file($settings);
				}
			} else if (is_array($settings)) {
				$loaded = $settings;
			}
			
			// overwrite default settings with loaded settings
			$write_ini = false;
			foreach ($this->settings as $setting => $defaultvalue) {
				if (isset($loaded[$setting])) {
					$this->settings[$setting] = $loaded[$setting];
				} else {
					// setting was not found in loaded values - if loaded from ini file set to rewrite file
					$write_ini = write_ini OR (is_string($settings) AND is_dir(dirname($settings)));
				}
			}
			
			// write ini with newer version if not complete
			if ($write_ini) {
				$content = "; General settings. Adjust to your needs.\n; ------------------------------ \n\n";
				foreach ($this->settings as $setting => $value) {
					if (isset(manager::$settings_descriptions[$setting])) $content .= "; ".manager::$settings_descriptions[$setting]."\n";
					$content .= $setting.' = "'.$value.'"'."\n\n"; 
				}
				file_put_contents($settings,$content);
			}			
		}
		
		/** Getter for the title of the manager.
		 * 
		 * @return string title of the manager
		 */
		public function get_title() {
			return $this->settings['title'];
		}
	}
?>
