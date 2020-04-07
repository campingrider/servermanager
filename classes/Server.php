<?php
/**
 * contains class Server
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager
 */

namespace campingrider\servermanager;

use campingrider\servermanager\exceptions\NotFoundException as NotFoundException;
use campingrider\servermanager\exceptions\ServerConfigurationException as ServerConfigurationException;
use campingrider\servermanager\exceptions\NotRunningException as NotRunningException;
use campingrider\servermanager\exceptions\UnexpectedStateException as UnexpectedStateException;
use campingrider\servermanager\utility\IniSettings as IniSettings;


/**
 * Class for representing and managing a single server machine
 *
 * Represents a single server machine and manages its services as well as the server itself.
 * Provides server status and the capability to turn the server off and on.
 *
 * @package campingrider\servermanager
 */
class Server
{
    /**
     * Contains description for all settings which can be written out to an ini file.
     *
     * @var string[] $settings_descriptions
     */
    private static $settings_descriptions = array(
        'title'       => 'The following phrase is shown as a title for the server',
        'mac_address' => 'The MAC Address of the server, used for starting the server via wake on lan. If not set there will be no start/stop button.',
        'ip'          => 'The IP Address of the server, used for connecting via ssh, ping etc.',
        'user'        => 'The server user which can be logged in via ssh (no login via password, only via key file!)'
    );

    /**
     * Default settings servers.
     *
     * @var string[] $settings_default
     */
    private static $settings_default = array(
        'title'       => 'Title for server not configured yet',
        'mac_address' => '',
        'ip'          => '',
        'user'        => 'root'
    );

    /**
     * The parental server manager managing this server.
     *
     * @var Manager $manager
     */
    private $manager;

    /**
     * The id of the server.
     *
     * @var string $server_id;
     */
    private $server_id;

    /**
     * The services running on this server.
     *
     * @var Service[] $services;
     */
    private $services = array();

    /**
     * The settings object managing all settings for this server.
     * 
     * @var IniSettings $settings
     */
    private $settings = null;

    /**
     * Constructor.
     *
     * Loads server configuration from the servers directory, specified by its id.
     *
     * @param Manager $manager The parental server manager managing this server.
     * @param string $server_id Internal id of the server; used to determine the relevant directory.
     * @throws NotFoundException Thrown if either directory or ini-file is not at the expected location.
     */
    public function __construct($manager, $server_id)
    {
        $this->manager = $manager;
        $this->server_id = $server_id;

        $dir_path = $this->getDirPath();
        $settings_path = $dir_path . '/settings.ini';

        // load settings
        $this->settings = new IniSettings($settings_path, Server::$settings_default, Server::$settings_descriptions);

        $this->loadServices();
    }

    /**
     * Creates the HTML representing the server.
     *
     * Contains the HTML created by the corresponding methods of the provided services.
     * Provides overall information on and funtionality of the specific server.
     *
     * @return string The assembled HTML code.
     */
    public function assembleHTML()
    {
        $status = $this->getStatus();

        $img = '<img style="width:1em;" ';
        $short = 'status-';
        switch ($status) {
            case Server::STATUS_LISTENING:
                $text = 'Bereit!';
                $short .= 'listening';
                $img .= 'alt="Bereit" src="./svg/check.svg">';
                break;
            case Server::STATUS_RUNNING:
                $text = 'Noch nicht bereit!';
                $short .= 'running';
                $img .= 'alt="Nicht Bereit" src="./svg/hourglass.svg">';
                break;
            case Server::STATUS_OFF:
            default:
                $text = 'Ausgeschaltet!';
                $short .= 'off';
                $img .= 'alt="Ausgeschaltet" src="./svg/close.svg">';
                break;
        }

        $html = '';
        $html .= '<section class="panel server ' . $short . '"';
        $html .= ' id="server-'.$this->getUniqueIdentifier().'">';
        $html .= "<header>";
        
        $html .= '<a href="#server-'.$this->getUniqueIdentifier();
        $html .= '">';

        $html .= '<img src="./svg/server.svg" alt="Server: ">';
        
        $html .= '</a>';

        $html .= '<h1><a href="#server-'.$this->getUniqueIdentifier();
        $html .= '">';
        $html .= $this->getTitle() . '</a></h1>';

        $html .= '<form action="" method="POST">';
        $html .= '<input type="hidden" name="server" value="' . $this->server_id . '">';
        $html .= '<input type="hidden" name="action" value="powerbutton">';

        // only add powerbutton if this server can be started via wakeonlan
        if (!empty($this->settings->get('mac_address'))) {
            $html .= '<button type="submit" title="An-/Abschalten">';
            $html .= '<img src="./svg/power-off.svg" alt="An-/Abschalten!">';
            $html .= '</button>';
        }

        $html .= '</form>';
        
        $html .= "</header>";

        if (!empty($this->services)) {
            $html .= '<ul class="services">';
            foreach ($this->services as $sid => $service) {
                $html .= $service->assembleListEntry();
            }
            $html .= '</ul>';
        }

        $html .= '<footer>';
        
        $html .= '<p> Serverstatus: ' . $text;
        $html .= '</p>';
        
        $html .= '<footer>';

        $html .= '</section>';

        return $html;
    }

    /**
     * Executes a command on the server.
     *
     * The command is invoked via ssh and executed with root user priviliges. Handle with care.
     *
     * @param string $command The command which shall be executed by the server.
     * @throws NotRunningException Thrown if the server is not running at the moment.
     * @return string The output given by the server
     */
    private function exec($command)
    {
        // prepare ssh call
        $ssh_command = "ssh ".$this->settings->get('user')."@" . $this->settings->get('ip');

        // try to call the server in order to ensure it is running
        if (strlen(\shell_exec($ssh_command)) === 0) {
            $message = 'The server you tried to issue a command on is not running.';
            throw new NotRunningException($message);
        }

        // assemble command and escape command
        $ssh_command .= ' ' . \escapeshellarg($command);
        return \shell_exec($ssh_command);
    }

    /**
     * Getter for the path to the directory.
     *
     * @throws NotFoundException Thrown if the directory is not at the expected location.
     * @return string The path to the directory.
     */
    public function getDirPath()
    {
        $dir_path = $this->manager->getServerDirPath() . '/' . $this->server_id;

        if (!is_dir($dir_path)) {
            throw new NotFoundException("Directory $dir_path not found!");
        }

        return $dir_path;
    }

    /**
     * Try to ping the server based on the current operating system.
     *
     * @return int Integer between 0 and 100 referring to the percentage of lost ping packets.
     */
    private function getLostPingPercentage()
    {
        $answer = '';
        if (strtoupper(\substr(php_uname('s'), 0, 3)) === 'WIN') {
            // we're running on windows
            $answer = shell_exec('ping ' . $this->settings->get('ip') . ' -n 1 -w 1');
        } else {
            // we're running on linux
            $answer = shell_exec('ping ' . $this->settings->get('ip') . ' -w 1');
        }
        $matches = array();
        preg_match('/(\d+)\%/', $answer, $matches);

        return intval($matches[1]);
    }

    /**
     * Getter for the services running on the server.
     *
     * @return Service[] list of services
     */
    public function getServices()
    {
        return $this->services;
    }

    const STATUS_OFF = 0;
    const STATUS_RUNNING = 1;
    const STATUS_LISTENING = 2;
    
    /**
     * Determines whether the server is running and listening for commands.
     *
     * @return int Integer describing current server status.
     */
    private function getStatus()
    {
        try {
            $this->exec('cat /etc/issue');
            return Server::STATUS_LISTENING;
        } catch (NotRunningException $e) {
            // try whether server responds to ping
            if ($this->getLostPingPercentage() < 100) {
                // TODO: ping-aufrufe vermeiden
                return Server::STATUS_RUNNING;
            } else {
                return Server::STATUS_OFF;
            }
        }

        return Server::STATUS_OFF;
    }

    /**
     * Getter for the title of the server.
     *
     * @return string title of the server
     */
    public function getTitle()
    {
        return $this->settings->get('title');
    }

    /**
     * Creates a globally unique identifier for this service
     */
    public function getUniqueIdentifier()
    {
        $id = $this->server_id;
        return $id;
    }

    /**
     * Load all Services from the servers directory.
     *
     * @throws NotFoundException Thrown if the directory is not found at the given location.
     *
     * @return void
     */
    private function loadServices()
    {
        $dir_path = $this->getDirPath();

        // traverse the server directory and create service objects.
        if (is_dir($dir_path) && $handle = opendir($dir_path)) {
            while (false !== ($entry = readdir($handle))) {
                // only create an object for ini files
                if ($entry != '.' && $entry != '..' && is_file($dir_path . '/' . $entry)) {
                    if (preg_match('/^(.+)\.service\.ini$/', $entry, $service_id)) {
                        $this->services[$service_id[1]] = new Service($this, $service_id[1]);
                    }
                }
            }
            closedir($handle);
        } else {
            throw new NotFoundException("Directory $dir_path not found!");
        }
        ksort($this->services, SORT_NATURAL);
    }

    // TODO: implement in a right manner
    public function processAction($action, ...$params)
    {
        if ($action == "powerbutton") {
            if (!empty($this->settings('mac_address'))) {
                try {
                    $shellreturn = $this->exec('shutdown -h now');
                    echo 'sent shutdown command to server; shutting down.';
                } catch (NotRunningException $e) {
                    $wakecommand = 'wakeonlan ' . $this->settings->get('mac_address');
                    $shellreturn = shell_exec($wakecommand);
                    echo 'executed ' . $wakecommand;
                    echo 'got in return: <pre>' . $shellreturn . '</pre>';
                } finally {
                    echo 'received call for action, will reload page in 20 seconds';

                    echo '<script>window.setTimeout(function() {
                        let loc = window.location.protocol + "//" + window.location.host + window.location.pathname; 
                        window.location.replace(loc); 
                    }, 20000);</script>';
                }
            } else {
                throw new NotFoundException('The server could not be shut down / started because there was no mac adress configured!');
            }
        }
    }
}
