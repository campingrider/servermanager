<?php
	namespace de\campingrider\servermanager;
	
	/**
	 * This class contains overall settings and controls the behavior of the system. 
	 * 
	 */
	 
	class manager {
		private static $settings_descriptions = new Array(
			'title'=>'The following phrase is shown as a title for the server manager',
			'banner_path'=>'The following path points to the banner image'
		);
		
		private $settings_general = new Array(
			'title'=>'',
			'banner_path'=>''
		);
	}
?>
