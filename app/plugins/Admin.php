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
 * @copyright  Copyright (c) 2012 Classy Llama Studios, LLC
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
     * Disable an administrative user.
     *
     * @param Username (optional)
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function disableAction($options) {
        $this->changeUserStatus($options, FALSE);
    }

    /**
     * Enable an administrative user.
     *
     * @param Username (optional)
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function enableAction($options) {
        $this->changeUserStatus($options, TRUE);
    }

    function changeUserStatus($options, $status) {
        $username = '';

        switch (count($options)) {
            case 1:
                $username = array_pop($options);
            default:
        }

        // Asks for what we don't have.
        while ($username == '') {
            printf('Login: ');
            $username = trim(fgets(STDIN));
        }

        Wiz::getMagento();

        $adminUser = Mage::getModel('admin/user')->loadByUsername($username);

        if (!$adminUser->getId()) {
            throw new Exception(sprintf('Unable to find user "%s"', $username));
        }

        $adminUser
            ->setIsActive($status)
            ->save();

        $output = array(array('Login' => $username, 'Status' => $status ? 'Active' : 'Inactive'));
        echo Wiz::tableOutput($output);
    }

    /**
     * Lists the backend users in Magento.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function listAction() {
        $output = array();

        Wiz::getMagento();

        $userCollection = Mage::getModel('admin/user')->getCollection();

        foreach ($userCollection as $user) {
            $output[] = array(
                'Id' => $user->getId(),
                'Username' => $user->getUsername(),
                'Email' => $user->getEmail(),
                'Status' => $user->getIsActive() ? 'Active' : 'Inactive',
            );
        }
        echo Wiz::tableOutput($output);
    }

    /**
     * Resets an admin user's password.  If you do not pass the parameters,
     * you will be prompted for them.
     * 
     * Options:
     *   --send-email   Will send the user an e-mail about their new
     *                  password.
     * 
     *   --random       Will generate a random password.
     * 
     *   --show         Will show the password saved to the user.
     *
     * @param Username (optional)
     * @param Password (optional)
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function resetpassAction($options) {
        $username = $password = '';

        foreach ($options as $option) {
            if (strpos(trim($option), '--') !== 0) {
                $realParams[] = $option;
            }
        }

        // Load up what we have.
        switch (count($realParams)) {
            case 2:
                $password = array_pop($realParams);
            case 1:
                $username = array_pop($realParams);
            default:
        }

        // Asks for what we don't have.
        while ($username == '') {
            printf('Login: ');
            $username = trim(fgets(STDIN));
        }

        while ($password == '' && !Wiz::getWiz()->getArg('random')) {
            printf('New Password: ');
            $password = trim(fgets(STDIN));
        };

        Wiz::getMagento();

        if (Wiz::getWiz()->getArg('random')) {
            $password = Mage::helper('core')->getRandomString(10);
        }

        $adminUser = Mage::getModel('admin/user')->loadByUsername($username);

        if (!$adminUser->getId()) {
            throw new Exception(sprintf('Unable to find user "%s"', $username));
        }

        $adminUser
            ->setPassword($password)
            ->save();

        if (Wiz::getWiz()->getArg('send-email')) {
            $adminUser->setPlainPassword($password);
            $adminUser->sendNewPasswordEmail();
        }

        if (Wiz::getWiz()->getArg('show')) {
            printf('Password for user "%s" has been changed to "%s".' . PHP_EOL, $username, $password);
        }
        else {
            printf('Password for user "%s" has been updated.' . PHP_EOL, $username);
        }
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
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Sets or displays the admin session timeout value.
     * 
     * Usage:
     *   wiz admin-timeout <time-value>
     * 
     * If <time-value> is not provided, it displays the value fof the admin session
     * timeout.  Otherwise, it sets the admin session timeout value to the provided
     * value.  Possible values:
     * 
     * <int>s - Sets the timeout to be the value.  Default if no multiplier is 
     *          specified.
     * <int>m  - Sets the timeout to be the number of minutes specified. (e.g. 30m)
     * <int>h  - Sets the timeout to be the number of hours specified. (e.g. 8h)
     * <int>d  - Sets the timeout to be the number of days specified. (e.g. 2d)
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function timeoutAction($options) {
        $time = array_pop($options);
        Wiz::getMagento();

        if ($time != '') {
            // Common multipliers.
            $multiplier = array('s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400);

            // Grab the components of the time.
            preg_match('#(\d+)(m|h|d)?#i', $time, $matches);

            $mult  = $matches[2] != '' ? $matches[2] : 's';
            $value = $matches[1] != '' ? $matches[1] : 0;

            // If we got passed some weird multiplier, bail out.
            if (!array_key_exists($mult, $multiplier)) {
                throw new Exception("Invalid time specifier: '$mult'.");
            }

            $timeInSeconds = (int)$value * $multiplier[$mult];

            // Kick back anything that is less than 60 seconds, since the admin would.
            if ($timeInSeconds < 60) {
                throw new Exception('Values less than 60 seconds are ignored.');
            } 

            // Save our value and then remove the cache.
            Mage::getConfig()->saveConfig('admin/security/session_cookie_lifetime', $timeInSeconds);
            Mage::getConfig()->removeCache();

            // We do this here because below doesn't run correctly until the next config load.
            $output = array(array('Config Value' => 'admin/security/session_cookie_lifetime', 'Value' => $timeInSeconds));
        }
        else {
            // Give 'em the value.
            $output = array(array('Config Value' => 'admin/security/session_cookie_lifetime', 'Value' => (int)Mage::getStoreConfig('admin/security/session_cookie_lifetime')));
        }

        echo Wiz::tableOutput($output);
    }

    public function _recurseXmlWalkResources($item, $path = '', $all = false) {
        $results = array();

        foreach ($item->children() as $itemName => $child) {
            if ('children' == $itemName) {
                $results = array_merge($results, $this->_recurseXmlWalkResources($child, $path, $all));
            }
            else if ('title' == $itemName && $path != '') {
                $results[] = array($path, (string)$child);
            }
            else {
                $results = array_merge($results, $this->_recurseXmlWalkResources($child, $path . ($path != '' ? '/' : '') . (string)$itemName, $all));
            }
        }
        return $results;
    }

    public function _sortAclEntries($a, $b) {
        return strcmp($a['Path'], $b['Path']);
    }


    /**
     * Displays a full list of resources that are defined by modules in Magento.
     *
     * @param string $options 
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function resourcesAction($options) {
        $formattedOutput = $output = array();

        Wiz::getMagento();
        $aclResources = Mage::getModel('admin/config')->getAdminhtmlConfig()->getNode("acl/resources");
        $output = $this->_recurseXmlWalkResources($aclResources->admin);

        foreach ($output as $data) {
            $formattedOutput[] = array('Path' => $data[0], 'Title' => $data[1]);
        }

        usort($formattedOutput, array($this, '_sortAclEntries'));

        echo Wiz::tableOutput($formattedOutput);
    }

}
