<?php
/**
 * File contains NotFoundException class
 *
 * @package campingrider\servermanager
 */

namespace campingrider\servermanager;

use \RuntimeException as RuntimeException;

/**
 * Exception class thrown whenever a directory or file is missing.
 *
 * @package campingrider\servermanager
 */
class NotFoundException extends RuntimeException
{
}
