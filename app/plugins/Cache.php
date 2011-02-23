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
 * Cache Plugin for Wiz
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
Class Wiz_Plugin_Cache extends Wiz_Plugin_Abstract {

    /**
     * Clear the Magento caches.  Same processes used by the Administrative backend.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function clearAction($options) {
        Wiz::getMagento();
        Mage::app()->getCacheInstance()->flush();

        # Clean the Magento storage cache.
        Mage::app()->cleanCache();
        echo 'Magento caches have been cleared.'.PHP_EOL;
        return TRUE;
    }

    function _getAllMagentoCacheTypes() {
        Wiz::getMagento();
        return Mage::app()->getCacheInstance()->getTypes();
    }

    /**
     * Enables all caches if "all" or no params are passed.  Otherwise it will enable
     * the specified caches.
     *
     * @param "all" to enable all caches, or a list of cache ids (see cache-status) 
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function enableAction($options) {
        $caches = $this->_getAllMagentoCacheTypes();

        $didAllCaches = FALSE;

        if (count($options) == 0 || (count($options) == 1 && $options[0] == 'all')) {
            foreach ($caches as $cache) {
                $cacheCodesToEnable[] = $cache->getId();
                $cacheNamesToEnable[] = $cache->getCacheType();
            }
            $didAllCaches = TRUE;
        }
        else {
            while (($cacheName = array_shift($options)) != '') {
                if ($cache = $caches[$cacheName]) {
                    $cacheCodesToEnable[] = $cacheName;
                    $cacheNamesToEnable[] = $cache->getCacheType();
                }
            }
        }

        $allTypes = Mage::app()->useCache();

        $updatedTypes = 0;
        foreach ($cacheCodesToEnable as $code) {
            if (empty($allTypes[$code])) {
                $allTypes[$code] = 1;
                $updatedTypes++;
            }
        }

        if ($updatedTypes > 0) {
            Mage::app()->saveUseCache($allTypes);
            if ($didAllCaches) {
                echo 'All caches are now enabled.'.PHP_EOL;
            }
            else {
                echo 'The following cache(s) were enable: '.implode(', ', $cacheNamesToEnable).PHP_EOL;
            }
        }
        else {
            echo 'Nothing was done.  Likely they were already enabled.'.PHP_EOL;
        }

        return TRUE;
    }

    function disableAction($options) {
        $types = $this->_getAllMagentoCacheTypes();
        var_dump($types);
        return TRUE;
    }

    function statusAction() {
        $types = $this->_getAllMagentoCacheTypes();
        echo 'Cache status'.PHP_EOL.str_repeat('-', 60).PHP_EOL;
        foreach ($types as $cache) {
            printf('%-30s (%15s): %10s'.PHP_EOL, $cache->getCacheType(), $cache->getId(), $cache->getStatus() ? 'Enabled' : 'Disabled');
        }
        return TRUE;
    }
}
