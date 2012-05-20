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
    public function showhintsAction($options) {
        /*
         * Per #7, if client restrictions are enabled, template hints won't work.
         * I'm not sure if it is a good idea to simply just disable the restrictions,
         * but we could alert the poor soul that they are set.
         */

        Wiz::getMagento();
        $value = Mage::getStoreConfig('dev/restrict/allow_ips');

        if ($value != NULL) {
            echo 'Developer restrictions are enabled.  This value has no effect.' . PHP_EOL;
        }

        $this->toggleConfigValue($options, array('dev/debug/template_hints', 'dev/debug/template_hints_blocks'));
    }

    /**
     * Enables, disables, or displays the status of logging in Magento.
     *
     * To show: wiz devel-logging
     *
     * To enable: wiz devel-logging <yes|true|1|totally>
     *
     * To disable: wiz devel-logging <no|false|0|nah>
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function loggingAction($options) {
        $this->toggleConfigValue($options, 'dev/log/active');
    }

    /**
     * Enables, disables, or displays the value of symlinks allowed for templates.
     *
     * To show: wiz devel-allowsymlinks
     *
     * To enable: wiz devel-allowsymlinks <yes|true|1|totally>
     *
     * To disable: wiz devel-allowsymlinks <no|false|0|nah>
     *
     * Only compatible with Magento 1.5.1.0+
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function allowsymlinksAction($options) {
        Wiz::getMagento();
        $this->toggleConfigValue($options, Mage_Core_Block_Template::XML_PATH_TEMPLATE_ALLOW_SYMLINK);
    }

    /**
     * Dumps a set of useful devel configuration values.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function configAction($options) {
        Wiz::getMagento();
        $values =
        array('dev/debug/profiler',
              'dev/js/merge_files',
              'dev/css/merge_css_files',
              'dev/log/active',
              'dev/debug/template_hints',
              'dev/debug/template_hints_blocks');

        /**
         * @todo Refactor this to look at values on a Magento version basis.  Not by trial and error.
         */
        if (defined('Mage_Core_Block_Template::XML_PATH_TEMPLATE_ALLOW_SYMLINK')) {
            $values[] = constant('Mage_Core_Block_Template::XML_PATH_TEMPLATE_ALLOW_SYMLINK');
        }

        $this->toggleConfigValue(array(),
            $values);
    }

    /**
     * Enables, disables, or displays the status of the profiler.
     *
     * To show: wiz devel-profiler
     *
     * To enable: wiz devel-profiler <yes|true|1|totally>
     *
     * To disable: wiz devel-profiler <no|false|0|nah>
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function profilerAction($options) {
        $this->toggleConfigValue($options, 'dev/debug/profiler');
    }

    /**
     * Enables, disables, or displays the status of JS Merging.
     *
     * To show: wiz devel-mergejs
     *
     * To enable: wiz devel-mergejs <yes|true|1|totally>
     *
     * To disable: wiz devel-mergejs <no|false|0|nah>
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function mergejsAction($options) {
        $this->toggleConfigValue($options, 'dev/js/merge_files');
    }

    /**
     * Enables, disables, or displays the status of CSS Merging.
     *
     * To show: wiz devel-mergecss
     *
     * To enable: wiz devel-mergecss <yes|true|1|totally>
     *
     * To disable: wiz devel-mergecss <no|false|0|nah>
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function mergecssAction($options) {
        $this->toggleConfigValue($options, 'dev/css/merge_css_files');
    }

    /**
     * Generic function to handle enabling, disabling or showing the value of one or
     * more configuration paths.
     *
     * @param array $options
     * @param array|string Configuration path or paths as an array.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    private function toggleConfigValue($options, $values) {
        if (!is_array($values)) {
            $values = array($values);
        }

        // Display the current values.
        $output = array();
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
                // @todo - Exception
                echo 'Invalid option: ' . $options[0] . PHP_EOL;
            }
        }

        foreach ($values as $value) {
            if ($showValue !== NULL) {
                Mage::getConfig()->saveConfig($value, $showValue);
            }
            else {
                $output[] = array(
                    'Path' => $value,
                    'Value' => (int)Mage::getConfig()->getNode($value, 'default') == 1 ? 'Yes' : 'No'
                );
            }
        }

        if ($showValue !== NULL) {
            Mage::getConfig()->removeCache();
        }

        if ($output) {
            echo Wiz::tableOutput($output);
        }
    }

    /**
     * Returns a list of registered event observers.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function listenersAction() {
        $modelMapping = array();
        Wiz::getMagento();
        $wiz = Wiz::getWiz();

        $modelMapping = array_merge($modelMapping, $this->getObserversForPath('global/events'));
        $modelMapping = array_merge($modelMapping, $this->getObserversForPath('frontend/events'));
        $modelMapping = array_merge($modelMapping, $this->getObserversForPath('adminhtml/events'));

        echo Wiz::tableOutput($modelMapping);
    }

    /**
     * returns a list of observers from the configuration XML from
     * a config path.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function getObserversForPath($path) {
        $config = Mage::getConfig();
        foreach ($config->getNode($path)->children() as $parent => $children) {
            foreach ($children->children() as $childName => $observerInfo) {
                if ((string)$childName !== 'observers') continue;

                foreach($observerInfo as $observerId => $info) {
                    $modelMapping[] = array(
                        'Dispatched event' => $parent,
                        'Module' => $observerId,
                        'Method' => $info->class . '::' . $info->method
                    );
                }
            }
        }
        return $modelMapping;
    }

    /**
     * Returns a list of model names to class maps.  This will also call out rewritten
     * classes so you can see what type of object you will get when you call
     * Mage::getModel(_something_).
     *
     *     +------------+-------------------+
     *     | Model Name | PHP Class         |
     *     +------------+-------------------+
     *     | varien/*   | Varien_*          |
     *     | core/*     | Mage_Core_Model_* |
     *     | ...        |                   |
     *     +------------+-------------------+
     *
     * Options:
     *      --all       (shows everything, default)
     *      --models    (shows only models, not resource models)
     *      --resources (shows only resource models, not models)
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function modelsAction() {
        Wiz::getMagento();
        $modelMapping = array();

        $config = Mage::getConfig();

        $showModels =  Wiz::getWiz()->getArg('models');
        $showResources =  Wiz::getWiz()->getArg('resources');

        if (Wiz::getWiz()->getArg('all') || (!$showModels && !$showResources)) {
            $showResources = $showModels = true;
        }

        foreach ($config->getNode('global/models')->children() as $parent => $children) {
            if (substr($parent, -7) == '_mysql4' && !$showResources || substr($parent, -7) != '_mysql4' && !$showModels)
                continue;
            foreach ($children->children() as $className => $classData) {
                switch ($className) {
                    case 'class':
                        $modelMapping[] = array(
                            'Model Name'    => $parent . '/*',
                            'PHP Class'     => (string)$classData.'_*',
                        );
                        break;
                    case 'rewrite':
                        foreach ($classData->children() as $rewriteName => $rewriteData) {
                            $modelMapping[] = array(
                                'Model Name'    => $parent . '/' . $rewriteName,
                                'PHP Class'     => (string)$rewriteData,
                            );
                        }
                    default:
                        break;
                }
            }
        }

        echo Wiz::tableOutput($modelMapping);
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
    public function eventsAction() {
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
            preg_match_all('#Mage::dispatchEvent\((.*)\);#m', $fileSource, $matches);
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
    }
}
