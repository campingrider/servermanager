<?php
/**
 * contains class ServerConfigurationException
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager\exceptions
 */

namespace campingrider\servermanager\exceptions;

use \RuntimeException as RuntimeException;

/**
 * Exception class thrown whenever the server (or webserver) can't process any action or command issued.
 *
 * @package campingrider\servermanager\exceptions
 */
class ServerConfigurationException extends RuntimeException
{
    // No customization implemented yet
}
