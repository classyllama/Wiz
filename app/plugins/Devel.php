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

class Wiz_Plugin_Devel extends Wiz_Plugin_Abstract {

    /**
     * Enables, disables, or displays the value of template hints.
     * 
     * To show: wiz devel-showhints
     * 
     * To enable: wiz devel-showhints <yes|true|1|totally>
     * 
     * To disable: wiz devel-showhints <no|false|0|nah>
     * 
     * Note: this will not affect sites if the template hints are overriden via the system
     * config in the dashboard... for now.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public static function showhintsAction($options) {
        // Display the current values.
        Wiz::getMagento();
        $showValue = NULL;
        if (count($options) > 0) {
            if (in_array(strtolower($options[0]), array('false', '0', 'no', 'nah'))) {
                $showValue = 0;
            }
            else if (in_array(strtolower($options[0]), array('true', '1', 'yes', 'totally'))) {
                $showValue = 1;
            }
            else {
                echo 'Invalid option: '.$options[0].PHP_EOL;
                return TRUE;
            }
        }

        foreach (array('dev/debug/template_hints', 'dev/debug/template_hints_blocks') as $shPath) {
            if ($showValue !== NULL) {
                Mage::getConfig()->saveConfig($shPath, $showValue);
            }
            else {
                $output[] = array(
                    'Path' => $shPath,
                    'Value' => (int)Mage::getConfig()->getNode($shPath, 'default') == 1 ? 'Yes' : 'No'
                );
            }
        }

        if ($showValue !== NULL) {
            Mage::getConfig()->removeCache();
        }

        if ($output) {
            echo Wiz::tableOutput($output);
        }
        return TRUE;
    }

    /**
     * Attempts to output a list of dispatched Magento Events.  Currently, it iterates
     * recursively over the app/ directory and looks for instances where Mage::dispatchEvent
     * is called.  It then outputs the first parameter as the "event."  Some events have
     * variables inside of them (like EAV product events and some controller events).
     *
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function eventsAction() {
        Wiz::getMagento();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Wiz::getMagentoRoot().DIRECTORY_SEPARATOR.'app'));
        foreach ($iterator as $file) {
            if (preg_match('#.php$#', $file->getFilename()))
                $phpFiles[] = $file->getRealpath();
        }
        $baseClasses = get_declared_classes();

        foreach ($phpFiles as $fileName) {
            $matches = array();
            include $fileName;
            // echo get_include_path().PHP_EOL;
            $extraClasses = get_declared_classes();
            // var_dump(array_diff($extraClasses, $baseClasses));
            $fileSource = file_get_contents($fileName);
            preg_match_all('#Mage::dispatchEvent\((.*)\);#m', $fileSource, &$matches);
            if (count($matches) > 1 && count($matches[1]) > 1) {
                foreach ($matches[1] as $match) {
                    if (strpos($match, ',') !== FALSE) {
                        $stuff = explode(',', $match);
                        $eventName = trim($stuff[0]);
                    }
                    else {
                        $eventName = $match;
                    }
                    if (substr($stuff[0], 0, 1) == "'" || substr($stuff[0], 0, 1) == '"') {
                        $eventName = substr(trim($stuff[0]), 1, -1);
                    }
                    $events[] = $eventName;
                }
            }
        }
        $events = array_unique($events);
        sort($events);
        foreach ($events as $eventName) {
            $eventOutput[] = array('Event Name' => $eventName);
        }
        echo Wiz::tableOutput($eventOutput);
        return TRUE;
    }    
}   
