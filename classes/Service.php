<?php
/**
 * contains class Service
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager
 */

namespace campingrider\servermanager;

use campingrider\servermanager\exceptions\NotFoundException as NotFoundException;
use campingrider\servermanager\exceptions\ServerConfigurationException as ServerConfigurationException;
use campingrider\servermanager\exceptions\ServiceConfigurationException as ServiceConfigurationException;
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
class Service
{
    /**
     * Contains description for all settings which can be written out to an ini file.
     *
     * @var string[] $settings_descriptions
     */
    private static $settings_descriptions = array(
        'title'       => 'The following phrase is shown as a title for the service',
        'id_internal' => 'Identifier for this service used by the Service/Daemon-system',
        'type'        => 'Type of the service, i.e. type of the service-system the service runs on'
    );

    /**
     * Default settings for services.
     *
     * @var string[] $settings_default
     */
    private static $settings_default = array(
        'title'       => 'Title for service not configured yet',
        'id_internal' => '',
        'type'        => 'systemd'
    );

    /**
     * The settings object managing all settings for this service.
     * 
     * @var IniSettings $settings
     */
    private $settings = null;

    /**
     * The server on which this service is running.
     *
     * @var Server $server
     */
    private $server;

    /**
     * The id of the service.
     *
     * @var string $service_id;
     */
    private $service_id;

    /**
     * Constructor.
     *
     * Loads service configuration from the servers directory, specified by its id.
     *
     * @param Server $server The server on which this service is running.
     * @param string $service_id Internal id of the service.
     * @throws NotFoundException Thrown if ini-file is not at the expected location.
     */
    public function __construct($server, $service_id)
    {
        $this->server = $server;
        $this->service_id = $service_id;

        $dir_path = $this->server->getDirPath();
        $settings_path = $dir_path . '/' . $service_id . '.service.ini';

        // load settings
        $this->settings = new IniSettings($settings_path, Service::$settings_default, Service::$settings_descriptions);
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
            case Service::STATUS_RUNNING:
                $text = 'Bereit!';
                $short .= 'running';
                $img .= 'alt="Bereit" src="./svg/check.svg">';
                break;
            case Service::STATUS_OFF:
            default:
                $text = 'Ausgeschaltet!';
                $short .= 'off';
                $img .= 'alt="Ausgeschaltet" src="./svg/close.svg">';
                break;
        }

        $html = '';
        $html .= '<section class="panel service ' . $short . '"';
        $html .= ' id="service-'.$this->getUniqueIdentifier().'">';
        $html .= "<header>";
        
        $html .= '<a href="#service-'.$this->getUniqueIdentifier();
        $html .= '">';
        
        $html .= '<img src="./svg/gears.svg" alt="Service: ">';

        $html .= '</a>';
        
        $html .= '<h1>';        
        $html .= '<a href="#service-'.$this->getUniqueIdentifier();
        $html .= '">';

        $html .= $this->getTitle();
        
        /*
        $html .= ' @ ';
        $html .= $this->server->getTitle();
        */

        $html .= '</a></h1>';

        /* This needs refactoring! Copy-Paste from Server code
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
        */

        $html .= "</header>";

        $html .= '<p class="server">';
        $html .= '<a href="#server-'.$this->server->getUniqueIdentifier().'">';
        $html .= '<img src="./svg/caret-up.svg" alt="Nach oben: ">';
        $html .= '<span>';       
        $html .= $this->server->getTitle();
        $html .= '</span>';
        $html .= '<img src="./svg/server.svg" alt="- Link zum Server">';
        $html .= '</a>';
        $html .= '</p>';

        $html .= '<p>Zu diesem Dienst liegen keine weiteren Informationen vor.</p>';

        $html .= '<footer>';
        
        $html .= '<p>Service Status: ' . $text;
        $html .= '</p>';
        
        $html .= '<footer>';

        $html .= '</section>';

        return $html;
    }

    /**
    * Creates the HTML representing the service in the list of services
    *
    * @return string The assembled HTML code.
    */
   public function assembleListEntry()
   {
       $html = ''; 
       $html .= '<li>';
       $html .= '<a href="#service-'.$this->getUniqueIdentifier().'">';
       $html .= '<img src="./svg/gears.svg" alt="Service: ">';
       $html .= '<span>';       
       $html .= $this->getTitle();
       $html .= '</span>';
       $html .= '<img src="./svg/caret-down.svg" alt=" - linked here!">';
       $html .= '</a>';
       $html .= '</li>';

       return $html;
   }

    const STATUS_OFF = 0;
    const STATUS_RUNNING = 1;
    
    /**
     * Determines whether the service is running.
     *
     * @throws ServiceConfigurationException if the command can not be issued properly
     * 
     * @return int Integer describing current service status.
     */
    private function getStatus()
    {
        if (empty($this->settings->get('id_internal'))) {
            return Service::STATUS_OFF;
        }

        switch ($this->settings->get('type')) {
            case 'systemd':
                try {
                    $shellreturn = $this->server->exec('systemctl is-active '.$this->settings->get('id_internal'));
                    if (strpos($shellreturn, 'active') !== false) {
                        return Service::STATUS_RUNNING;
                    } else {
                        return Service::STATUS_OFF;
                    }
                } catch (NotRunningException $e) {
                    return Service::STATUS_OFF;
                } 
                break;
            default:
                throw new ServiceConfigurationException('Type '.$this->settings->get('type').' of service '.$this->getUniqueIdentifier().' is unknown.');
        }
    }

    /**
     * Getter for the title of the service.
     *
     * @return string title of the service
     */
    public function getTitle()
    {
        return $this->settings->get('title');
    }

    /**
     * Creates a manager-wide unique identifier for this service
     */
    public function getUniqueIdentifier()
    {
        $id = $this->service_id . '-' . $this->server->getUniqueIdentifier();
        return $id;
    }

    /**
     * Determines whether the service is enabled.
     *
     * @throws ServiceConfigurationException if the command can not be issued properly
     * 
     * @return boolean whether the service is enabled or not
     */
    private function is_enabled()
    {
        if (empty($this->settings->get('id_internal'))) {
            return false;
        }

        switch ($this->settings->get('type')) {
            case 'systemd':
                try {
                    $shellreturn = $this->server->exec('systemctl is-enabled '.$this->settings->get('id_internal'));
                    return (strpos($shellreturn, 'active') !== false);
                } catch (NotRunningException $e) {
                    return false;
                }
                break;
            default:
                throw new ServiceConfigurationException('Type '.$this->settings->get('type').' of service '.$this->getUniqueIdentifier().' is unknown.');
        }
    }

    // TODO: implement in a right manner
    public function processAction($service_id, $action, ...$params)
    {
        return "Nothing done...";
    }
}
