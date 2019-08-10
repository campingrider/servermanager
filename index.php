<?php
    namespace campingrider\servermanager;

    session_start();
    
    spl_autoload_register(function ($name) {
        if (strpos($name, __NAMESPACE__) === 0) {
            $classpath = './classes/';
            $classpath .= str_replace('\\', DIRECTORY_SEPARATOR, str_replace(__NAMESPACE__ . '\\', '', $name)) . '.php';
            if (is_file($classpath)) {
                include_once($classpath);
            } else {
                die(
                    'File for required class '
                    . $name .
                    ' was searched for at '
                    . $classpath .
                    ' but could not be found.'
                );
            }
        }
    });
    
    $manager = new Manager("./custom/settings.ini");

    ?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
        <link rel="stylesheet" href="css/layout.css" type="text/css">
        <title><?php echo $manager->getTitle(); ?> - RIDERs Server Manager</title>
        <!-- 
        This software uses icons from Font-Awesome, http://fontawesome.io, released under SIL OFL 1.1.
        The svg files used were created from font-awesome by Font-Awesome-SVG-PNG, 
        https://github.com/encharm/Font-Awesome-SVG-PNG, released under MIT license. 
        -->
    </head>
    <body>
        <header>
            <h1><?php echo $manager->getTitle(); ?></h1>
        </header>
        <main>
            <?php echo $manager->assembleHTML(); ?>
            <?php
            // TODO: implement in a right manner
            if (isset($_POST['action']) && isset($_POST['server'])) {
                $manager->processAction($_POST['server'], $_POST['action']);
            }
            ?>
        </main>
        <footer>  
            <?php
            if (array_key_exists('info', $_GET)) {
                echo '<div>';
                phpinfo();
                echo '</div>';
            }
            ?>
        </footer>
    </body>
</html>
