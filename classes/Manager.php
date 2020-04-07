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
use campingrider\servermanager\exceptions\UnexpectedStateException as UnexpectedStateException;
use campingrider\servermanager\utility\IniSettings as IniSettings;

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
        'users_path'      => 'The following path points to the file where user info is stored',
        'groups_path'     => 'The following path points to the file where group info is stored',
        'add_localhost'   => '"off": Don\'t add localhost as manageable server, "on": Automatically add localhost as manageable server. When turning off this setting, the server-directory for localhost needs to be removed.'
    );

    /**
     * Default settings for the server manager.
     *
     * @var string[] $settings_default
     */
    private static $settings_default = array(
        'title'           => 'Title not configured yet',
        'banner_path'     => 'no_path.png',
        'server_dir_path' => './servers',
        'users_path'      => './custom/users.ini',
        'groups_path'     => './custom/groups.ini',
        'add_localhost'   => 'off'
    );

    /**
     * Security handler working for this manager.
     *
     * @var security\Doorman doorman
     */
    private $doorman;

    /**
     * Array of all the servers managed by this manager.
     *
     * @var Server[] $servers
     */
    private $servers = array();

    /**
     * The settings object managing all settings for this server manager.
     * 
     * @var IniSettings $settings
     */
    private $settings = null;

    /**
     * Constructor.
     *
     * @param string $settings_path Path of an ini-File containing all settings which shall be loaded.
     * @throws NotFoundException Thrown if the ini-File is not found at the given location.
     */
    public function __construct($settings_path)
    {   
        // load settings
        $this->settings = new IniSettings($settings_path, Manager::$settings_default, Manager::$settings_descriptions);       

        $this->doorman = new security\Doorman($this->settings->get('users_path'), $this->settings->get('groups_path'));

        $this->loadServers();
    }

    /**
     * Add a new Server to the directory configured by settings.
     *
     * @throws NotFoundException Thrown if the directory is not found at the given location.
     * @throws InvalidArgumentException Thrown if the server id does contain invalid characters.
     * @throws UnexpectedStateException Thrown if a server with this id already exists or the creation of the service failed for another reason.
     *
     * @return void
     */
    private function addServer($server_id, $settings_string = "")
    {
        if (preg_match('/[^a-z0-9_-]/', $server_id)) {
            throw new InvalidArgumentException("The server id may only contain characters a-z, 0-9, - or _ and '$server_id' did not match this restriction.");
        }

        $dir_path = $this->getServerDirPath();

        if (is_dir($dir_path)) {
            $server_dir = $dir_path . '/' . $server_id;
            $not_already_there = !is_dir($server_dir);
            if ($not_already_there && mkdir($server_dir)) {
                $server_settings_path = $server_dir . '/' . 'settings.ini';
                file_put_contents($server_settings_path,$settings_string);
            } else {
                throw new UnexpectedStateException("The directory $server_dir does already exist or could not be created.");
            }
        } else {
            throw new NotFoundException("Directory $dir_path not found!");
        }
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
        $html .= '<section id="servers" class="panel-container">';
        foreach ($this->servers as $server) {
            $html .= $server->assembleHTML();
        }
        $html .= '</section>';
        $html .= '<section id="services" class="panel-container">';
        $html .= '</section>';
        return $html;
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
     * Getter for the path of the directory containing the server configuration directories.
     *
     * @return string path of the directory containing the server configuration directories.
     */
    public function getServerDirPath()
    {
        return $this->settings->get('server_dir_path');
    }

    /**
     * Getter for the title of the manager.
     *
     * @return string title of the manager
     */
    public function getTitle()
    {
        return $this->settings->get('title');
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

        // automatically add localhost if configured this way
        if ($this->settings->get('add_localhost') == 'on') {
            try {
                $this->addServer('localhost', 'title = "localhost"' . "\n" . 'ip = "127.0.0.1"');
            } catch(UnexpectedStateException $e) {
                // do nothing - localhost might already be added
            }
        }
            
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
        ksort($this->servers, SORT_NATURAL);
    }

    // TODO: implement in a right manner
    public function processAction($serverid, $action, ...$params)
    {
        $this->servers[$serverid]->processAction($action, $params);
    }
}
