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
 * Configuration Plugin for Wiz
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
class Wiz_Plugin_Config extends Wiz_Plugin_Abstract {

    /**
     * Returns the value stored in Magento for the given config path.
     *
     * @param Configuration path.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
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
    }

    /**
     * Retrieve a single store configuration node path.
     *
     * Example: config-storget sales_email/order/enabled
     * This will return the value in the configuration if the order e-mails are enabled.
     *
     * Options:
     *   --all         Returns the value for all stores in the Magento installation.
     *
     * @param Node path string.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function storegetAction($options) {

        $stores = $output = array();

        Wiz::getMagento();

        if (Wiz::getWiz()->getArg('all')) {
            $storeCollection = Mage::getModel('core/store')->getCollection();
            foreach ($storeCollection as $store) {
                $stores[] = $store->getCode();
            }
        }
        elseif (count($options) > 1) {
            $stores = array(array_shift($options));
        }
        else {
            $stores = array('default');
        }

        $path = array_shift($options);

        foreach ($stores as $store) {
            $output[] = array('Store Id' => $store, $path => Mage::getStoreConfig($path, $store));
            // echo "($store) $path" . ' = ' . Mage::getStoreConfig($path, $store);// Wiz::getMagento()->
        }
        echo Wiz::tableOutput($output);
        echo PHP_EOL;
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
     *   --ugly (optional)   Makes the output ugly (no tabs or newlines)
     *
     *   --system            Returns the modules configuration (system.xml)
     *
     *   --indent <int>      Number of spaces to use to indent the XML.  The
     *                       default is 3.
     *
     * @return The Magento Configuration as as nicely printed XML File.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function asxmlAction() {
        Wiz::getMagento();

        if (Wiz::getWiz()->getArg('system')) {
            $xml = Mage::getConfig()->loadModulesConfiguration('system.xml');
        }
        else {
            $xml = Mage::getConfig();
        }

        if (!Wiz::getWiz()->getArg('ugly')) {
            $output = $xml->getNode()->asNiceXml('');

            // Update with our indentation if so desired.
            if (Wiz::getWiz()->getArg('indent') !== false) {
                $output = preg_replace_callback('#^(\s+)#m', array($this, '_replaceSpaces'), $output);
            }
        }
        else {
            $output = $xml->getNode()->asXml();
        }

        echo $output;
        echo PHP_EOL;
    }

    /**
     * Replace the standard 3 spaces asXml products with whatever we want.
     *
     * @param string $matches
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    protected function _replaceSpaces($matches) {
        $newSpaces = (int)Wiz::getWiz()->getArg('indent');
        return str_repeat(' ', (strlen($matches[1]) / 3) * $newSpaces);
    }
}
