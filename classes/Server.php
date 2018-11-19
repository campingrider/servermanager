<?php
/**
 * File contains Server class
 *
 * @package campingrider\servermanager
 */

namespace campingrider\servermanager;

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
        $html .= '<h1>Server: ' . $this->getTitle() . '</h1>';
        $html .= "</header>";

        $html .= '<form action="" method="GET">';
        $html .= '<input type="hidden" name="server" value="' . $this->server_id . '">';
        $html .= '<input type="hidden" name="action" value="powerbutton">';
        $html .= '<button type="submit"><img  style="width:2em;" src="./svg/power-off.svg"></button>';
        $html .= '</form>';

        return $html;
    }

    // TODO: implement in a right manner
    public function processAction($action, ...$params)
    {
        if ("powerbutton" == $action) {
            $shutdowncommand = "ssh root@" . $this->settings['ip'] . " 'shutdown -h now'";
            $shellreturn = shell_exec(shutdowncommand);
            echo 'executed ' . $shutdowncommand;
            echo 'got in return: <pre>' . $shellreturn . '</pre>';
            if (false !== strpos($shellreturn, 'No route to host')) {
                $wakecommand = 'wakeonlan ' . $this->settings['mac_address'];
                $shellreturn = shell_exec($wakecommand);
                echo 'executed ' . $wakecommand;
                echo 'got in return: <pre>' . $shellreturn . '</pre>';
            }
        }
    }
}