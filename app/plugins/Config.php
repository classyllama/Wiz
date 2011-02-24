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
                // foreach ($result->children() as $child) {
                    // $childArray[] = (string)$child;
                // }
                // var_dump($result);
                $childArray = array_keys($result->asArray());
                var_dump($childArray);
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
     * Example: config-storget sales_email/order/enabled
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

        echo "($store) $path" . ' = ' . Mage::getStoreConfig($path);// Wiz::getMagento()->
        echo PHP_EOL;
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
     *
     * @param ugly (optional) - Makes the output ugly (no tabs or newlines)
     * @return The Magento Configuration as as nicely printed XML File.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function asxmlAction($options) {
        $ugly = 0;
        if (count($options) > 0 && strtolower($options[0]) == 'ugly') {
            $ugly = NULL;
        }
        Wiz::getMagento();
        echo Mage::getConfig()->getNode()->asNiceXml('', $ugly);
        echo PHP_EOL;
        return TRUE;
    }

    // public function getallAction($options) {
    //     // print(__FILE__.':'.__LINE__.'#'.__CLASS__.'->'.__METHOD__);
    //     $magentoConfig = Wiz::getMagento()->getConfig();
    // 
    //     
    // 
    //     echo PHP_EOL;
    //     return TRUE;
    // }
    
    public function setAction($options) {
        
    }
    
    public function listAction($options) {
        var_dump(Wiz::getMagento()->getConfig());
    }
}

