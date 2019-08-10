<?php
/**
 * contains class Manager
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager
 */

namespace campingrider\servermanager;

use \InvalidArgumentException as InvalidArgumentException;
use campingrider\servermanager\exceptions\NotFoundException as NotFoundException;

/**
 * This class contains overall settings and controls the behavior of the system.
 *
 * @package campingrider\servermanager
 */
class Manager
{

    /**
     * Security handler working for this manager.
     *
     * @var security\Doorman doorman
     */
    private $doorman;

    /**
     * Contains description for all settings which can be written out to an ini file.
     *
     * @var string[] $settings_descriptions
     */
    private static $settings_descriptions = array(
        'title'           => 'The following phrase is shown as a title for the server manager',
        'banner_path'     => 'The following path points to the banner image',
        'server_dir_path' => 'Path to the directory containing the configuration directories of all the servers',
        'users_path'      => 'The following path points to the file where user info is stored',
        'groups_path'     => 'The following path points to the file where group info is stored'
    );

    /**
     * Settings for the server manager.
     *
     * Contains settings for the server manager; initially contains default values for all settings.
     *
     * @var string[] $settings
     */
    private $settings = array(
        'title'           => 'Title not configured yet',
        'banner_path'     => 'no_path.png',
        'server_dir_path' => './servers',
        'users_path'      => './custom/users.ini',
        'groups_path'      => './custom/groups.ini'
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
                throw new NotFoundException("File $settings not found!");
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

        $this->doorman = new security\Doorman($this->settings['users_path'], $this->settings['groups_path']);

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
            throw new NotFoundException("Directory $dir_path not found!");
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
            $html .= $server->assembleHTML();
        }
        return $html;
    }

    /**
     * Evaluates the Path to the root of the application.
     *
     * Internally, $_SERVER['SCRIPT_NAME'] is used, so if that is not available, the app will not be able to run.
     *
     * @throws \Exception Thrown if $_SERVER doesn't contain necessary information.
     *
     * @return string Path from domain to root of application.
     */
    private function getPathPrefix()
    {
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            throw new Exception('Failure: Can not determine SCRIPT_NAME.');
        }

        $prefix = substr($_SERVER['SCRIPT_NAME'], 0, -10);

        return $prefix;
    }

    /**
     * Evaluates the current Path relative to the root of the application.
     *
     * Internally, $_SERVER['REQUEST_URI'] is used, so if that is not available, the app will not be able to run.
     *
     * @throws \Exception Thrown if $_SERVER doesn't contain necessary information.
     *
     * @return string Path from root of application to current page.
     */
    public function getCurrentPath()
    {
        $prefix = $this->getPathPrefix();
        $curPath = substr($_SERVER['REQUEST_URI'], strlen($prefix));
        return $curPath;
    }

    // TODO: implement in a right manner
    public function processAction($serverid, $action, ...$params)
    {
        $this->servers[$serverid]->processAction($action, $params);
    }
}
