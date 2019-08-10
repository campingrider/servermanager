<?php
/**
 * contains class NotRunningException
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager\exceptions
 */

namespace campingrider\servermanager\exceptions;

use \RuntimeException as RuntimeException;

/**
 * Exception class thrown whenever a server or service is not running although it should be.
 *
 * @package campingrider\servermanager\exceptions
 */
class NotRunningException extends RuntimeException
{
    // No customization implemented yet
}
