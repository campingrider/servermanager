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
        'mac_address' => 'The MAC Address of the server, used for starting the server via wake on lan',
        'ip'          => 'The IP Address of the server, used for connecting via ssh, ping etc.',
    );

    /**
     * Settings for the server.
     *
     * Contains settings for the server; initially contains default values for all settings.
     *
     * @var string[] $settings
     */
    private $settings = array(
        'title'       => 'Title for server not configured yet',
        'mac_address' => '',
        'ip'          => '',
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

        $loaded_settings = array();

        $dir_path = $this->manager->getServerDirPath() . '/' . $server_id;
        $settings_path = $dir_path . '/settings.ini';

        // load settings from file.
        if (is_dir($dir_path)) {
            if (is_file($settings_path)) {
                $loaded_settings = parse_ini_file($settings_path);
            } else {
                throw new NotFoundException("File $settings_path not found!");
            }
        } else {
            throw new NotFoundException("Directory $dir_path not found!");
        }

        // overwrite default settings with loaded settings.
        $write_ini = false;
        foreach ($this->settings as $setting_id => $defaultvalue) {
            if (isset($loaded_settings[ $setting_id ])) {
                $this->settings[ $setting_id ] = $loaded_settings[ $setting_id ];
            } else {
                // setting was not found in loaded values - so it needs to be written to the settings file!
                $write_ini = true;
            }
        }

        // write ini with newer version if ini was not complete.
        if ($write_ini) {
            $content = "; Settings for server with id $server_id. Adjust to your needs.";
            $content .= "\n; ------------------------------ \n\n";
            foreach ($this->settings as $setting_id => $value) {
                if (isset(Server::$settings_descriptions[ $setting_id ])) {
                    $content .= '; ' . Server::$settings_descriptions[ $setting_id ] . "\n";
                }
                $content .= $setting_id . ' = "' . $value . '"' . "\n\n";
            }
            file_put_contents($settings_path, $content);
        }
    }

    /**
     * Getter for the title of the server.
     *
     * @return string title of the server
     */
    public function getTitle()
    {
        return $this->settings['title'];
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
        $html = '';
        $html .= "<header>";
        $html .= '<h1><img src="./svg/server.svg" alt="Server: ">' . $this->getTitle() . '</h1>';
        $html .= "</header>";

        $html .= '<form action="" method="POST">';
        $html .= '<input type="hidden" name="server" value="' . $this->server_id . '">';
        $html .= '<input type="hidden" name="action" value="powerbutton">';
        $html .= '<button type="submit" title="An-/Abschalten">';
        $html .= '<img style="width:2em;" src="./svg/power-off.svg" alt="An-/Abschalten!">';
        $html .= '</button>';
        $html .= '<p>Status: ';

        $html .= '<img style="width:2em;" ';

        $status = $this->getStatus();

        switch ($status) {
            case Server::STATUS_LISTENING:
                $html .= 'alt="Bereit" src="./svg/check.svg">';
                break;
            case Server::STATUS_RUNNING:
                $html .= 'alt="Nicht Bereit" src="./svg/hourglass.svg">';
                break;
            case Server::STATUS_OFF:
            default:
                $html .= 'alt="Ausgeschaltet" src="./svg/close.svg">';
                break;
        }

        $html .= '</p>';
        $html .= '</form>';

        // TODO: Implement in a right manner
        $html .= '<h2>Ping-Abfrage (Serverstatus)</h2>';
        // $html .= '<pre>Ping-Abfrage aktuell nicht verfügbar.</pre>';
        $html .= '<pre>' . shell_exec('ping ' . $this->settings['ip'] . ' -w 1') . '</pre>';

        return $html;
    }

    // TODO: implement in a right manner
    public function processAction($action, ...$params)
    {
        if ("powerbutton" == $action) {
            try {
                $shellreturn = $this->exec('shutdown -h now');
                echo 'sent shutdown command to server; shutting down.';
            } catch (NotRunningException $e) {
                $wakecommand = 'wakeonlan ' . $this->settings['mac_address'];
                $shellreturn = shell_exec($wakecommand);
                echo 'executed ' . $wakecommand;
                echo 'got in return: <pre>' . $shellreturn . '</pre>';
            }

            echo 'received call for action, will reload page in 20 seconds';

            echo '<script>window.setTimeout(function() {
                let loc = window.location.protocol + "//" + window.location.host + window.location.pathname; 
                window.location.replace(loc); 
            }, 20000);</script>';
        }
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
        $ssh_command = "ssh root@" . $this->settings['ip'];

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
     * Try to ping the server based on the current operating system.
     *
     * @return int Integer between 0 and 100 referring to the percentage of lost ping packets.
     */
    private function getLostPingPercentage()
    {
        $answer = '';
        if (strtoupper(\substr(php_uname('s'), 0, 3)) === 'WIN') {
            // we're running on windows
            $answer = shell_exec('ping ' . $this->settings['ip'] . '-n 1 -w 1');
        } else {
            // we're running on linux
            $answer = shell_exec('ping ' . $this->settings['ip'] . ' -w 1');
        }
        $matches = array();
        preg_match('/(\d)+\%/', $answer, $matches);

        print_r($matches);

        return intval($matches[1]);
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
            $this->exec('');
            return Server::STATUS_LISTENING;
        } catch (NotRunningException $e) {
            // try whether server responds to ping
            if ($this->getLostPingPercentage() < 100) {
                return Server::STATUS_RUNNING;
            } else {
                return Server::STATUS_OFF;
            }
        }

        return Server::STATUS_OFF;
    }
}
