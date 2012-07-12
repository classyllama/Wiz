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
 * @copyright  Copyright (c) by 2012 Classy Llama Studios, LLC
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
     * If called as "wiz cache-clear" then we will clear the 
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function clearAction($options) {
        Wiz::getMagento();

        if (count($options) == 0) {
            $options[] = 'default';
        }

        $types = array_keys($this->_getAllMagentoCacheTypes());

        if (count($options) == 1 && !in_array($options[0], $types)) {
            switch ($options[0]) {
                case 'invalidated':
                    $this->_cleanCachesById(array_keys(Mage::app()->getCacheInstance()->getInvalidatedTypes()));
                    break;
                case 'system':
                    $this->_cleanSystem();
                    break;
                case 'js':
                case 'css':
                case 'jscss':
                    $this->_cleanMedia();
                    break;
                case 'images':
                    $this->_cleanImages();
                    break;
                case 'all':
                    $this->_cleanSystem();
                    $this->_cleanMedia();
                    $this->_cleanImages();
                    $this->_cleanAll();
                    break;
                case 'default':
                    $this->_cleanAll();
                    break;
                default:
            }
        }
        else {
            $this->_cleanCachesById($options);
        }
    }

    function _cleanCachesById($options) {
        $caches = $this->_getAllMagentoCacheTypes();
        $cachesCleaned = array();

        foreach ($options as $type) {
            try {
                Mage::app()->getCacheInstance()->cleanType($type);
                $cachesCleaned[] = $caches[$type]->getCacheType();
            }
            catch (Exception $e) {
                echo 'Failed to clear cache: ' . $type . PHP_EOL;
            }
        }

        if (count($cachesCleaned) > 0) {
            echo 'The following caches have been cleaned: ' . implode(', ', $cachesCleaned) . PHP_EOL;
        }
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
    }

    /**
     * Disables caches by name.
     *
     * @param One or more caches, separated by a space.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function disableAction($options) {
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
            if (!empty($allTypes[$code])) {
                $allTypes[$code] = 0;
                $updatedTypes++;
            }
            Mage::app()->getCacheInstance()->cleanType($code);
        }

        if ($updatedTypes > 0) {
            Mage::app()->saveUseCache($allTypes);
            if ($didAllCaches) {
                echo 'All caches are now disabled.'.PHP_EOL;
            }
            else {
                echo 'The following cache(s) were disabled: '.implode(', ', $cacheNamesToEnable).PHP_EOL;
            }
        }
        else {
            echo 'Nothing was done.  Likely they were already disabled.'.PHP_EOL;
        }
    }


    /**
     * Returns the status of all or named caches.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function statusAction() {
        $types = $this->_getAllMagentoCacheTypes();
        $invalidatedTypes = Mage::app()->getCacheInstance()->getInvalidatedTypes();

        foreach ($types as $cache) {
            $rows[] = array(
                'Type' => $cache->getCacheType(),
                'Id' => $cache->getId(),
                'Status' => isset($invalidatedTypes[$cache->getId()]) ? 'Invalidated' : ($cache->getStatus() ? 'Enabled' : 'Disabled'),
            );
        }
        echo Wiz::tableOutput($rows);
    }

    public function _cleanAll() {
        Mage::dispatchEvent('adminhtml_cache_flush_all');
        Mage::app()->getCacheInstance()->flush();
        echo 'The cache storage has been flushed.' . PHP_EOL;
    }

    public function _cleanSystem() {
        Mage::app()->cleanCache();
        Mage::dispatchEvent('adminhtml_cache_flush_system');
        echo 'The Magento cache storage has been flushed.' . PHP_EOL;
    }

    public function _cleanMedia() {
        try {
            Mage::getModel('core/design_package')->cleanMergedJsCss();
            Mage::dispatchEvent('clean_media_cache_after');
            echo 'The JavaScript/CSS cache has been cleaned.' . PHP_EOL;
        }
        catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Clean JS/css files cache
     */
    public function _cleanImages()
    {
        try {
            Mage::getModel('catalog/product_image')->clearCache();
            Mage::dispatchEvent('clean_catalog_images_cache_after');
            echo 'The image cache was cleaned.' . PHP_EOL;
        }
        catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}
