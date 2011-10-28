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
 * Configuration Plugin for Wiz
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
class Wiz_Plugin_Config extends Wiz_Plugin_Abstract {

    public function getAction($options) {
        // Mage_Core_Model_Config_Element
        $result = Wiz::getMagento()->getConfig()->getNode($options[0]);
        // we'll either get a false or a Mage_Core_Model_Config_Element object
        $value = $message = null;
        if (is_object($result)) {
            if ($result->hasChildren()) {
                $childArray = array_keys($result->asArray());
                $value = '['.implode(', ', $childArray).']';
            }
            else {
                if ((string)$result == '') {
                    $value = '<empty>';
                }
                else {
                    $value = $result;
                }
            }
            echo $options[0] . ($value ? ' = ' . $value : ' ' . $message).PHP_EOL;
        }
        elseif ($result === FALSE) {
            echo 'Configuration path "' . $options[0] . '" not found.'.PHP_EOL;
        }
        return TRUE;
    }

    /**
     * Retrieve a single store configuration node path.
     * 
     * Example: config-storeget sales_email/order/enabled
     * This will return the value in the configuration if the order e-mails are enabled.
     *
     * @param Node path string.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function storegetAction($options) {
        $store = 'default';
        if (count($options) > 1) {
            $store = array_shift($options);
        }
        $path = array_shift($options);
        
        Wiz::getMagento();

        $value = Mage::getStoreConfig($path);// Wiz::getMagento()->
        if (is_array($value))
        	$textValue = '['.implode(', ', array_keys($value)).']';
        else
        	$textValue = $value;
        echo "($store) $path" . ' = ' . $textValue .PHP_EOL; 
        return TRUE;
    }

    /**
     * Performs an Xpath query over the Magento configuration structure and returns the
     * results as text selectors.  This should allow you to quickly find anything inside
     * of the configuration provided you know the node name you want.  The results will
     * have the entire hierarchy displayed so you'll know the exact location inside of
     * the document.  For instance, to display the version of every module on the system,
     * you could run: config-xpath //version and it will return every node named version
     * anywhere in the config.  You might get a lot of results using this command, but
     * just remember that Magento's config is HUGE.
     *
     * @param Xpath string to search.  For more information:
     * @see http://www.w3schools.com/xpath/xpath_syntax.asp
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function xpathAction($options) {
        Wiz::getMagento();
        $xpathResults = Mage::getConfig()->getXpath($options[0]);
        // We get an array of results back.
        foreach ($xpathResults as $result) {
            $parentArray = array();
            $parent = $result;
            while (($parent = $parent->getParent()) != NULL) {
                $parentArray[] = $parent->getName();
            }
            $parentArray = array_reverse($parentArray);
            $this->_recurseXpathOutput($parentArray, $result);
        }
        return TRUE;
    }

    private function _recurseXpathOutput($parents, $xmlelement) {
        array_push($parents, $xmlelement->getName());
        if ($xmlelement->hasChildren()) {
            foreach ($xmlelement->children() as $child) {
                $this->_recurseXpathOutput($parents, $child);
            }
        }
        else {
            echo implode('/', $parents).' = '.(string)$xmlelement.PHP_EOL;
        }
        array_pop($parents);
    }

    /**
     * Returns the entire Magento config as nicely formatted XML to stdout.
     * Options:
     *  --ugly (optional) - Makes the output ugly (no tabs or newlines)
     * 
     * @return The Magento Configuration as as nicely printed XML File.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function asxmlAction() {
        Wiz::getMagento();
        if (Wiz::getWiz()->getArg('ugly')) {
            echo Mage::getConfig()->getNode()->asXml();
        }
        else {
            echo Mage::getConfig()->getNode()->asNiceXml();
        }
        echo PHP_EOL;
        return TRUE;
    }

    public function setAction($options) {
        $scopeCode = 'default';
        $scopeId = 0;

        foreach (array('store', 'website') as $scope) {
            if (($argScopeCode = Wiz::getWiz()->getArg($scope)) !== FALSE) {
                // If --store is specified, but not provided, use the default.
                $scopeCode = $argScopeCode === TRUE ? '' : $argScopeCode;
                $scopeId = $scope;
                $thing = array_search($scope, $options);
                if ($thing !== FALSE) {
                    unset($options[$thing]);
                    unset($options[$thing+1]);
                }
                break;
            }
        }

        Wiz::getMagento();
        var_dump(array_shift($options), array_shift($options), $scopeCode, $scopeId);
        Mage::getConfig()->saveConfig(array_shift($options), array_shift($options), $scopeId, $scopeCode);
        $cacheSystem = new Wiz_Plugin_Cache();
        $cacheSystem->_cleanCachesById(array('config'));
        return TRUE;
    }

    /**
     * config-defaultset CONFIG_PATH VALUE
     * @param array $options
     * @return boolean true if successful
     */
    public function defaultsetAction($options) {
    	$configPath = $options[0];
    	$value = $options[1];
    	$scope = 'default';
    	$scopeId = 0;
    	
    	$config = Wiz::getMagento()->getConfig();
    	// this will take effect at *next* invocation
    	$config->saveConfig($configPath, $value, $scope, $scopeId);
    	echo "[$scope.$scopeId] $configPath = $value". PHP_EOL;
    	return true;
    }
}

