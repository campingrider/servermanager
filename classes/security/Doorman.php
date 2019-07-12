<?php
/**
 * contains class Doorman
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager\security
 */

namespace campingrider\servermanager\security;

/**
 * This class manages access, authentication and authorisation.
 *
 * @package campingrider\servermanager\security
 */
class Doorman
{
    /**
     * Path of the ini-File where user info is stored.
     *
     * @var string $users_path
     */
    private $users_path = '';

    /**
     * Path of the ini-File where group info is stored.
     *
     * @var string $groups_path
     */
    private $groups_path = '';

    /**
     * Constructor.
     *
     * @param string $users_path Path of an ini-File where user info is stored.
     * @param string $groups_path Path of an ini-File where group info is stored.
     *
     * @throws NotFoundException Thrown if the ini-File is not found at the given location.
     * @throws InvalidArgumentException Thrown if $settings is not of type string or array.
     */
    public function __construct($users_path, $groups_path)
    {
        // store parameters in private storage
        $this->users_path = $users_path;
        $this->groups_path = $groups_path;
    }
}
