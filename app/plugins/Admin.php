<?php

Class Wiz_Plugin_Admin extends Wiz_Plugin_Abstract {
    
    /**
     * Creates an administrative user.  Attempts to use the posix user information if
     * it is available.
     *
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    function _prefillUserData($options) {
        $returnArray = array();
        if (function_exists('posix_getpwnam')) {
            $userInfo = posix_getpwnam(posix_getlogin());
            list($first, $last) = explode(' ', $userInfo['gecos']);
            $returnArray['firstName'] = $first;
            $returnArray['lastName'] = $last;
            $returnArray['login'] = posix_getlogin();
        }
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
     * Creates an admin user in the Magento backend.  It uses the SQL script created
     * by Classy Llama Studios.
     * 
     * If you pass no parameters, it attempts to use posix data to pre-fill the fields
     * and will prompt you to confirm.  You can also pass it the following parameters
     * in order and it will create a user without prompting.
     * 
     * admin-createadmin <username> <firstname> <lastname> <email> <password>
     * 
     * The password will be MD5-hashed for you when the user is created.
     * 
     * @see http://classyllama.com/development/magento-development/add-magento-admin-account-using-mysql-script/
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

        // extract($defaults);

        Wiz::getMagento();
        // Magento CE
        $versionInfo = Mage::getVersionInfo();

        if ($versionInfo['major'] == 1 && ($versionInfo['minor'] > 2 && $versionInfo['minor'] < 6)) {
            $insertUser = "INSERT INTO admin_user SELECT
            (SELECT MAX(user_id) + 1 FROM admin_user) user_id,
            '$firstName' first_name,
            '$lastName' last_name,
            '$emailAddress' email,
            '$login' username,
            MD5('$password') password, /* You can replace this value with an md5 hash */
            NOW() created,
            NULL modified,
            NULL logdate,
            0 lognum,
            0 reload_acl_flag,
            1 is_active,
            (SELECT MAX(extra) FROM admin_user WHERE extra IS NOT NULL) extra;".PHP_EOL;

            $makeUserAdmin = "INSERT INTO admin_role
            SELECT
            (SELECT MAX(role_id) + 1 FROM admin_role) role_id,
            (SELECT role_id FROM admin_role WHERE role_name = 'Administrators') parent_id,
            2 tree_level,
            0 sort_order,
            'U' role_type,
            (SELECT user_id FROM admin_user WHERE username = '$login') user_id,
            '$login' role_name;".PHP_EOL;
        }
        else {
            echo 'Creating new user on PE/EE...'.PHP_EOL;
            $hashedPassword = hash('sha256', $password); // PE and EE use a SHA-256 hash
            $insertUser = "INSERT INTO admin_user SELECT
            NULL user_id,
            '$firstName' first_name,
            '$lastName' last_name,
            '$emailAddress' email,
            '$login' username,
            '$hashedPassword' password,
            NOW() created,
            NULL modified,
            NULL logdate,
            0 lognum,
            0 reload_acl_flag,
            1 is_active,
            (SELECT MAX(extra) FROM admin_user WHERE extra IS NOT NULL) extra,
            0 failures_num,
            NULL first_failure,
            NULL lock_expires;".PHP_EOL;

            $makeUserAdmin = "INSERT INTO admin_role
            SELECT
            (SELECT MAX(role_id) + 1 FROM admin_role) role_id,
            (SELECT role_id FROM admin_role WHERE role_name = 'Administrators') parent_id,
            2 tree_level,
            0 sort_order,
            'U' role_type,
            (SELECT user_id FROM admin_user WHERE username = '$login') user_id,
            '$login' role_name;".PHP_EOL;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_setup');
        try {
            $connection->multi_query($insertUser.PHP_EOL.$makeUserAdmin);
        }
        catch (Exception $e) {
            var_dump($e);
            return FALSE;
        }
        echo "Created new user '$login' with password '$password'".PHP_EOL;
        return TRUE;
    }
}