<?php
/**
 * contains class UnexpectedStateException
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager\exceptions
 */

namespace campingrider\servermanager\exceptions;

use \RuntimeException as RuntimeException;

/**
 * Exception class thrown whenever the server is not able to execute an issued command because the state of any service or server has already changed.
 *
 * @package campingrider\servermanager\exceptions
 */
class UnexpectedStateException extends RuntimeException
{
    // No customization implemented yet
}
