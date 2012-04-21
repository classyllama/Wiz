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
 * Magento information class.
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
Class Wiz_Plugin_Magento extends Wiz_Plugin_Abstract {

    /**
     * Returns the version of Magento that Wiz is currently pointing to.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function versionAction() {
        Wiz::getMagento();
        echo Mage::getVersion().PHP_EOL;
    }

    /**
     * Executes PHP file after bootstrapping Magento.
     * 
     * You can optionally specify the store under which to execute the script by passing
     * --store <storecode>.
     * 
     * @param filename
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function scriptAction($options) {
        if (count($options) < 1) {
            echo 'Please enter a script to execute.'.PHP_EOL;
            return FALSE;
        }
        elseif (!is_readable($options[0])) {
            echo 'Please enter a valid filename to execute.'.PHP_EOL;
            return FALSE;
        }
        else {
            $path = realpath($options[0]);
            Wiz::getMagento();
            include $path;
        }
    }
}