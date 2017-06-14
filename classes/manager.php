<?php
	namespace de\campingrider\servermanager;
	
	/**
	 * This class contains overall settings and controls the behavior of the system. 
	 * 
	 */
	 
	class manager {
		private static $settings_descriptions = array(
			'title'=>'The following phrase is shown as a title for the server manager',
			'banner_path'=>'The following path points to the banner image'
		);
		
		private $settings_general = array(
			'title'=>'',
			'banner_path'=>''
		);
	}
?>
