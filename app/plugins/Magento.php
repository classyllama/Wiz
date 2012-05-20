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
     * You can optionally specify to display Varien_Profiler data by passing --profile.
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
            
            // We have to check the settings AFTER we bootstrap Magento so that we can use the Mage class.
            if (Wiz::getWiz()->getArg('profile')) {
            	if (!Mage::getStoreConfig('dev/debug/profiler')	|| !Mage::helper('core')->isDevAllowed()) {
            		echo 'Please turn on the Varien_Profiler by executing the "devel-profiler yes" command'.PHP_EOL;;
            		return FALSE;
            	} else {
            		$profiling = true;
            	}
            }
            
            include $path;
            
            if ($profiling) {
            	$this->_flushProfileData();
            }
        }
    }

    /**
     * Shuts down Magento by creating the Maintenance flag.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function shutdownAction() {        
        if (($magentoRoot = Wiz::getMagentoRoot()) === FALSE) {
            throw new Exception('Unable to find Magento.');
        }

        $maintenanceFile = $magentoRoot . WIZ_DS . 'maintenance.flag';

        if (file_exists($maintenanceFile)) {
            echo 'Maintenance file already exists.' . PHP_EOL;
            return;
        }

        if (!is_writable($magentoRoot)) {
            throw new Exception('Cannot create maintenance flag file.  Is the directory writable?');
        }

        touch($maintenanceFile);

        echo 'Magento maintenance flag has been created.' . PHP_EOL;
    }
    
    /**
     * Displays the Varien_Profiler data to the screen.  If it is not enabled, it will 
     * indicate that it is disabled.
     * 
     * @author Ben Robie <brobie@gmail.com>
     **/
    function _flushProfileData(){
    	
    	$timers = Varien_Profiler::getTimers();
    	
    	foreach ($timers as $name=>$timer) {
    		$sum = Varien_Profiler::fetch($name,'sum');
    		$count = Varien_Profiler::fetch($name,'count');
    		$realmem = Varien_Profiler::fetch($name,'realmem');
    		$emalloc = Varien_Profiler::fetch($name,'emalloc');
    		if ($sum<.0010 && $count<10 && $emalloc<10000) {
    			continue;
    		}
    		
    		$output[] = array(
    				'Code Profiler' => $name,
    				'Time' => $sum,
    				'Cnt' => (string) $count,
    				'Emalloc' => (string) number_format($emalloc),
    				'RealMem' => (string) number_format($realmem),
    		);
    		
    		
    	}
    	echo Wiz::tableOutput($output);
    }
    
    /**
     * Removes the maintenance flag, allowing Magento to run.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function startAction() {        
        if (($magentoRoot = Wiz::getMagentoRoot()) === FALSE) {
            throw new Exception('Unable to find Magento.');
        }

        $maintenanceFile = $magentoRoot . WIZ_DS . 'maintenance.flag';

        if (!file_exists($maintenanceFile)) {
            echo 'Maintenance file does not exist.' . PHP_EOL;
            return;
        }

        if (!is_writable($maintenanceFile)) {
            throw new Exception('Cannot remove maintenance flag file.  Is the directory writable?');
        }

        unlink($maintenanceFile);

        echo 'Magento maintenance flag has been removed.' . PHP_EOL;
    }
}
