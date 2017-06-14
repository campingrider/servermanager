<?php
	namespace de\campingrider\servermanager;
	
	spl_autoload_register(function ($name) {
		if (strpos($name,__NAMESPACE__) === 0) {
			$classpath = './classes/'.str_replace('\\',DIRECTORY_SEPARATOR,str_replace(__NAMESPACE__.'\\','',$name)).'.php';
			if (is_file($classpath)) {
				include_once($classpath);
			} else {
				die('File for required class '.$name.' was searched for at '.$classpath.' but could not be found.');
			}
		}
	});
?>
<!DOCTYPE html>
<html lang="de">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
		<title>RIDERs Server Manager</title>
		<!-- 
		This software uses icons from Font-Awesome, http://fontawesome.io, released under SIL OFL 1.1.
		The svg files used were created from font-awesome by Font-Awesome-SVG-PNG, https://github.com/encharm/Font-Awesome-SVG-PNG, released under MIT license. 
		-->
	</head>
	<body>
	</body>
</html>