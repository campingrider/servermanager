<?php
/**
 * contains class NotFoundException
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager\exceptions
 */

namespace campingrider\servermanager\exceptions;

use \RuntimeException as RuntimeException;

/**
 * Exception class thrown whenever a directory or file is missing.
 *
 * @package campingrider\servermanager\exceptions
 */
class NotFoundException extends RuntimeException
{
    // No customization implemented yet
}
