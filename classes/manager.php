<?php
/**
 * Central class managing all features and content
 *
 * Cares for managing all servers and services and displays them to the user.
 *
 * @package campingrider\servermanager
 */

namespace campingrider\servermanager;

use \InvalidArgumentException as InvalidArgumentException;

/**
 * This class contains overall settings and controls the behavior of the system.
 *
 * @package campingrider\servermanager
 */
class Manager
{

    /**
     * Contains description for all settings which can be written out to an ini file.
     *
     * @var string[] $settings_descriptions
     */
    private static $settings_descriptions = array(
        'title'           => 'The following phrase is shown as a title for the server manager',
        'banner_path'     => 'The following path points to the banner image',
        'server_dir_path' => 'Path to the directory containing the configuration directories of all the servers',
    );

    /**
     * Settings for the server manager.
     *
     * Contains settings for the server manager; initially contains default values for all settings.
     *
     * @var string[] $settings
     */
    private $settings = array(
        'title'       => 'Title not configured yet',
        'banner_path' => 'no_path.png',
        'server_dir_path' => './servers'
    );

    /**
     * Array of all the servers managed by this manager.
     *
     * @var Server[] $servers
     */
    private $servers = array();

    /**
     * Constructor.
     *
     * @param string|string[] $settings Either string as Path of an ini-File containing all settings which shall
     *                                  be loaded or Array of strings containing all settings which shall be loaded.
     * @throws NotFoundException Thrown if the ini-File is not found at the given location.
     * @throws InvalidArgumentException Thrown if $settings is not of type string or array.
     */
    public function __construct($settings)
    {

        $loaded_settings = array();

        // load either from file or from array.
        if (is_string($settings)) {
            if (is_file($settings)) {
                $loaded_settings = parse_ini_file($settings);
            } else {
                throw new RuntimeException("File $settings not found!");
            }
        } elseif (is_array($settings)) {
            $loaded_settings = $settings;
        } else {
            throw new InvalidArgumentException(
                '$settings needs to be of type string or string[], not ' . gettype($settings) . '!'
            );
        }

        // overwrite default settings with loaded settings.
        $write_ini = false;

        foreach ($this->settings as $setting_id => $defaultvalue) {
            if (isset($loaded_settings[ $setting_id ])) {
                $this->settings[ $setting_id ] = $loaded_settings[ $setting_id ];
            } else {
                // setting was not found in loaded values - if loaded from ini file set to rewrite file!
                $write_ini = $write_ini || (is_string($settings) && is_dir(dirname($settings)));
            }
        }

        // write ini with newer version if ini was not complete.
        if ($write_ini) {
            $content = "; General settings. Adjust to your needs.\n; ------------------------------ \n\n";
            foreach ($this->settings as $setting_id => $value) {
                if (isset(Manager::$settings_descriptions[ $setting_id ])) {
                    $content .= '; ' . Manager::$settings_descriptions[ $setting_id ] . "\n";
                }
                $content .= $setting_id . ' = "' . $value . '"' . "\n\n";
            }
            file_put_contents($settings, $content);
        }

        $this->loadServers();
    }

    /**
     * Load all Servers from the directory configured by settings.
     *
     * @throws NotFoundException Thrown if the directory is not found at the given location.
     *
     * @return void
     */
    private function loadServers()
    {
        $dir_path = $this->getServerDirPath();

        // traverse the server config directory and create server objects.
        if (is_dir($dir_path) && $handle = opendir($dir_path)) {
            while (false !== ($entry = readdir($handle))) {
                // only create an object for directory
                if ($entry != '.' && $entry != '..' && is_dir($dir_path . '/' . $entry)) {
                    $this->servers[$entry] = new Server($this, $entry);
                }
            }
            closedir($handle);
        } else {
            throw new RuntimeException("Directory $dir_path not found!");
        }
    }

    /**
     * Getter for the title of the manager.
     *
     * @return string title of the manager
     */
    public function getTitle()
    {
        return $this->settings['title'];
    }

    /**
     * Getter for the path of the directory containing the server configuration directories.
     *
     * @return string path of the directory containing the server configuration directories.
     */
    public function getServerDirPath()
    {
        return $this->settings['server_dir_path'];
    }

    /**
     * Creates the HTML representing the server manager.
     *
     * Contains the HTML created by the corresponding methods of the managed servers.
     * Provides overall information and functionality.
     *
     * @return string The assembled HTML code.
     */
    public function assembleHTML()
    {
        $html = '';
        foreach ($this->servers as $server) {
            $html .= '<section>';
            $html .= $server->assembleHTML();
            $html .= '</section>';
        }
        return $html;
    }

    // TODO: implement in a right manner
    public function processAction($serverid, $action, ...$params)
    {
        $this->servers[$serverid]->processAction($action, $params);
    }
}
