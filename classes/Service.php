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
        'title'       => 'The following phrase is shown as a title for the service'
    );

    /**
     * Default settings for services.
     *
     * @var string[] $settings_default
     */
    private static $settings_default = array(
        'title'       => 'Title for service not configured yet',
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
    const STATUS_LISTENING = 2;
    
    /**
     * Determines whether the service is running.
     *
     * @return int Integer describing current service status.
     */
    private function getStatus()
    {
        return Service::STATUS_OFF;
    }
}
