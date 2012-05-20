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

class Wiz_Plugin_Module extends Wiz_Plugin_Abstract {

    /**
     * Lists all of the modules that are currently installed on the Magento installation, 
     * the version in their xml file, the version of the setup resource in the database,
     * their code pool, and what their active flag is.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function listAction() {
        Wiz::getMagento();
        $modules = (array)Mage::getConfig()->getNode('modules')->children();
        $moduleList = array();

        $coreResource = Mage::getSingleton('core/resource');
        $connection  = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $connection->select()->from($coreResource->getTableName('core/resource'), array('code' , 'version'));

        $result = $connection->fetchPairs($select);

        foreach(Mage::getConfig()->getNode('global/resources')->children() as $resourceData) {
            $resourceMappings[(string)$resourceData->setup->module] = $resourceData->getName();
        }

        foreach ($modules as $moduleName => $moduleData) {
            $flag = strtolower(Mage::getConfig()->getNode('advanced/modules_disable_output/' . $moduleName, 'default'));

            $moduleList[] = array(
                'Module Name' => $moduleName,
                'Version (xml)' => (string)$moduleData->version,
                'Version (db)' => $resourceMappings[$moduleName] != '' ? (string)$result[$resourceMappings[$moduleName]] : 'n/a',
                'Active' => $moduleData->active ? 'Active' : 'Disabled',
                'Output' => !empty($flag) && 'false' !== $flag ? 'Disabled' : 'Enabled',
                'Code Pool' => $moduleData->codePool,
            );
        }

        echo Wiz::tableOutput($moduleList);
    }

    /**
     * Enables a module.  You can pass a module name or a list of module names seperated
     * by spaces.
     * 
     * Usage: wiz module-enable NS_ModuleName NS_ModuleName2
     *
     * @param List of modules to enable seperated by spaces.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function enableAction($options) {
        if (count($options) < 1) {
            throw new Exception('Please provide the names of modules to enable.');
        }
        $modulesEnabled = $modulesAlreadyEnabled = array();
        Wiz::getMagento();
        $modules = (array)Mage::getConfig()->getNode('modules')->children();

        foreach ($options as $moduleName) {
            foreach ($modules as $systemModuleName => $moduleData) {
                if (strtolower($moduleName) == strtolower($systemModuleName)) {
                    $filesToCheck[] = BP . DS . 'app' . DS . 'etc' . DS . 'modules' . DS . $systemModuleName . '.xml';

                    if (!file_exists($filesToCheck[0])) {
                        $filesToCheck += glob(BP . DS . 'app' . DS . 'etc' . DS . 'modules' . DS . substr($systemModuleName, 0, strpos($systemModuleName, '_') + 1) . '*');
                    }

                    $file = array_shift($filesToCheck);

                    do {
                        $configFile = simplexml_load_file($file);
                        if ($configFile->modules->{$systemModuleName}->active == 'true') {
                            $modulesAlreadyEnabled[] = $systemModuleName;
                        }
                        else {
                            $configFile->modules->{$systemModuleName}->active = 'true';
                            $configFile->asXml($file);
                            $modulesEnabled[] = $systemModuleName;
                        }
                    } while (($file = array_shift($filesToCheck)) != NULL);

                    break;
                }
            }
        }

        if (count($modulesEnabled) > 0) {
            echo 'Module(s) enabled: '.implode(', ', $modulesEnabled).  PHP_EOL;
            Mage::getConfig()->removeCache();
        }

        if (count($modulesAlreadyEnabled)) {
            echo 'Module(s) already enabled: '.implode(', ', $modulesAlreadyEnabled).  PHP_EOL;
        }
    }

    /**
     * You can pass a module name or a list of module names seperated
     * by spaces.
     * 
     * Usage: wiz module-disable NS_ModuleName1 NS_ModuleName2
     * 
     * Please note: modules with dependencies will not be disabled unless all of the
     * dependencies are disabled first.  Modules with loaded dependencies will not be
     * disabled until those dependencies themselves are disabled.
     * 
     * @param List of modules to disable seperated by spaces.
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function disableAction($options) {
        if (count($options) < 1) {
            echo 'Please provide modules names to disable.'.PHP_EOL;
        }
        $modulesDisabled = $modulesAlreadyDisabled = $filesToCheck = array();
        Wiz::getMagento();
        $disabled = $alreadyDisabled = FALSE;
        $modules = (array)Mage::getConfig()->getNode('modules')->children();

        foreach ($modules as $moduleName => $moduleData) {
            if ($moduleData->depends) {
                foreach (array_keys((array)$moduleData->depends) as $depModule) {
                    $depends[$depModule][] = $moduleName;
                }
            }
        }

        foreach ($options as $moduleName) {
            $filesToCheck = array();

            foreach ($modules as $systemModuleName => $moduleData) {
                if (strtolower($moduleName) == strtolower($systemModuleName)) {

                    if (array_key_exists($systemModuleName, $depends) and count($depends[$systemModuleName]) > 0) {
                        echo 'Skipping ' . $systemModuleName . ' due to dependencies: ' . implode(', ', $depends[$systemModuleName]).PHP_EOL;
                        break;
                    }

                    $filesToCheck[] = BP . DS . 'app' . DS . 'etc' . DS . 'modules' . DS . $systemModuleName . '.xml';

                    if (!file_exists($filesToCheck[0])) {
                        $filesToCheck += glob(BP . DS . 'app' . DS . 'etc' . DS . 'modules' . DS . substr($systemModuleName, 0, strpos($systemModuleName, '_') + 1) . '*');
                    }

                    $file = array_shift($filesToCheck);

                    do {
                        $configFile = simplexml_load_file($file);
                        if ($configFile->modules->{$systemModuleName}->active == 'false') {
                            $modulesAlreadyDisabled[] = $systemModuleName;
                            break 2;
                        }
                        else {
                            $configFile->modules->{$systemModuleName}->active = 'false';
                            $configFile->asXml($file);
                            $modulesDisabled[] = $systemModuleName;
                            break 2;
                        }
                    } while (($file = array_shift($filesToCheck)) != NULL);

                    break 2;
                }
            }
        }

        if (count($modulesDisabled) > 0) {
            echo 'Module(s) disabled: '.implode(', ', $modulesDisabled).  PHP_EOL;
            Mage::getConfig()->removeCache();
        }

        if (count($modulesAlreadyDisabled)) {
            echo 'Module(s) already disabled: '.implode(', ', $modulesAlreadyDisabled).  PHP_EOL;
        }
    }

    /**
     * Enables output for a module.  This performs the same task as the Disable Module
     * Output page in the Magento backend.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function enableoutputAction($options) {
        if (count($options) < 1) {
            echo 'Please provide modules names to enable output on.'.PHP_EOL;
        }
        $modulesEnabled = $modulesAlreadyEnabled = array();

        Wiz::getMagento();

        $modules = (array)Mage::getConfig()->getNode('modules')->children();

        foreach ($options as $moduleName) {
            foreach ($modules as $systemModuleName => $moduleData) {
                if (strtolower($moduleName) == strtolower($systemModuleName)) {
                    $flag = strtolower(Mage::getConfig()->getNode('advanced/modules_disable_output/' . $systemModuleName, 'default'));
                    if (!empty($flag) && 'false' !== $flag) {
                        Mage::getConfig()->saveConfig('advanced/modules_disable_output/' . $systemModuleName, FALSE);
                        $modulesEnabled[] = $systemModuleName;
                    }
                    else {
                        $modulesAlreadyEnabled[] = $systemModuleName;
                    }
                    break;
                }
            }
        }

        if (count($modulesEnabled) > 0) {
            echo 'Module(s) output enabled: '.implode(', ', $modulesEnabled).  PHP_EOL;
            Mage::getConfig()->removeCache();
        }

        if (count($modulesAlreadyEnabled)) {
            echo 'Module(s) output already enabled: '.implode(', ', $modulesAlreadyEnabled).  PHP_EOL;
        }
    }

    /**
     * Disables output for a module.  This performs the same task as the Disable Module
     * Output page in the Magento backend.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function disableoutputAction($options) {
        if (count($options) < 1) {
            echo 'Please provide modules names to enable.'.PHP_EOL;
        }
        $modulesDisable = $modulesAlreadyDisabled = array();
        Wiz::getMagento();
        $modules = (array)Mage::getConfig()->getNode('modules')->children();

        foreach ($options as $moduleName) {
            foreach ($modules as $systemModuleName => $moduleData) {
                if (strtolower($moduleName) == strtolower($systemModuleName)) {
                    $flag = strtolower(Mage::getConfig()->getNode('advanced/modules_disable_output/' . $systemModuleName, 'default'));
                    if (empty($flag) || 'false' === $flag) {
                        Mage::getConfig()->saveConfig('advanced/modules_disable_output/' . $systemModuleName, TRUE);
                        // self::changeModuleOutput($systemModuleName, 'disabled');
                        $modulesDisabled[] = $systemModuleName;
                    }
                    else {
                        $modulesAlreadyDisabled[] = $systemModuleName;
                    }
                    break;
                }
            }
        }

        if (count($modulesDisabled) > 0) {
            echo 'Module(s) disabled: '.implode(', ', $modulesDisabled).  PHP_EOL;
            Mage::getConfig()->removeCache();
        }

        if (count($modulesAlreadyDisabled)) {
            echo 'Module(s) already disabled: '.implode(', ', $modulesAlreadyDisabled).  PHP_EOL;
        }
    }
}
