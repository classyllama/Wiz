<?php
/**
 * Wiz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 * 
 * This program is provided to you AS-IS.  There is no warranty.  It has not been
 * certified for any particular purpose.
 *
 * @package    Wiz
 * @author     Nick Vahalik <nick@classyllama.com>
 * @copyright  Copyright (c) 2011 Classy Llama Studios
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Admin Plugin for Wiz
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
Class Wiz_Plugin_Admin extends Wiz_Plugin_Abstract {
    
    /**
     * Creates an administrative user.  Attempts to use the posix user information if
     * it is available.
     *
     * @return array Assocative values to be used when creating the admin account.
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    function _prefillUserData($options) {

        // Prepopulate so E_STRICT doesn't complain on production servers.
        $returnArray = array(
            'login' => '',
            'firstName' => '',
            'lastName' => '',
            'emailAddress' => '',
            'password' => ''
        );

        if (count($options) == 5) {
            $returnArray['login'] = array_shift($options);
            $returnArray['firstName'] = array_shift($options);
            $returnArray['lastName'] = array_shift($options);
            $returnArray['emailAddress'] = array_shift($options);
            $returnArray['password'] = array_shift($options);
        }

        return $returnArray;
    }

    /**
     * Creates an admin user in the Magento backend.
     * 
     * If you pass no parameters, it attempts to use posix data to pre-fill the fields
     * and will prompt you to confirm.  You can also pass it the following parameters
     * in order and it will create a user without prompting.
     * 
     * admin-createadmin <username> <firstname> <lastname> <email> <password>
     * 
     * The password will be MD5-hashed for you when the user is created.
     * 
     * @param create user options
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function createadminAction($options) {
        $defaults = $this->_prefillUserData($options);

        if (count($options) != 5) {
            do {
                printf('Login [%s]: ', $defaults['login']);
                $login = (($input = trim(fgets(STDIN))) != '' ? $input : $defaults['login']);
            } while ($login == '');

            do {
                printf('First name [%s]: ', $defaults['firstName']);
                $firstName = ($input = trim(fgets(STDIN))) != '' ? $input : $defaults['firstName'];
            } while ($firstName == '');

            do {
                printf('Last name [%s]: ', $defaults['lastName']);
                $lastName = ($input = trim(fgets(STDIN))) != '' ? $input : $defaults['lastName'];
            } while ($lastName == '');

            do {
                printf('E-mail address: ');
                $emailAddress = trim(fgets(STDIN));
            } while ($emailAddress == '');

            do {
                printf('Password: ');
                $password = trim(fgets(STDIN));
            } while ($password == '');
        }
        else {
            extract($defaults);
        }

        Wiz::getMagento();

        $versionInfo = Mage::getVersionInfo();

        try {
            // Create the user
            $userModel = Mage::getModel('admin/user')
                ->setUsername($login)
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setEmail($emailAddress)
                ->setPassword($password)
                ->setIsActive(true)
                ->save();

            // Load the role collection
            $collection = Mage::getResourceModel('admin/role_collection');
            // Only load the roles, not the relationships
            $collection->setRolesFilter();

            // Find the administrative role.
            foreach ($collection as $role) {
                if (($versionInfo['major'] == 1 && ($versionInfo['minor'] > 2 && $versionInfo['minor'] < 6) 
                  && $role->getGwsIsAll() == 1) || $role->getRoleName() == 'Administrators') {
                    $userRoles[] = $role->getId();
                }
            }

            // Set up the role relationship
            $userModel->setRoleIds($userRoles)
                ->setRoleUserId($userModel->getUserId())
                ->saveRelations();

            echo "Created new user '$login' with password '$password'.".PHP_EOL;
            return TRUE;
        }
        catch (Exception $e) {
            echo 'Unable to create user: ' . $e->getMessage() . PHP_EOL;
            return FALSE;
        }
    }
}