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
 * @author     Ben Robie <brobie@gmail.com>
 * @copyright  Copyright (c) 2011 Classy Llama Studios
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Encryption Plugin for Wiz
 *
 * @author Ben Robie <brobie@gmail.com>
 */
Class Wiz_Plugin_Encrypt extends Wiz_Plugin_Abstract {
  
    /**
     * Creates a new encryption key and returns it to the screen
     * 
     * Command: wiz encrypt-resetKey
     *
     * @author Ben Robie <brobie@gmail.com>
     */
    function resetKeyAction() {
	 	Wiz::getMagento();

	 	if (Mage::getConfig()->getModuleConfig('Enterprise_Pci')){
		 	$file = Mage::getBaseDir('etc') . DS . 'local.xml';
		 	if (!is_writeable($file)) {
		 		throw new Exception('File %s is not writeable.', realpath($file));
		 	}
		 	$contents = file_get_contents($file);
		 	if (null === $key) {
		 		$key = md5(time());
		 	}
		 	$encryptor = clone Mage::helper('core')->getEncryptor();
		 	$encryptor->setNewKey($key);
		 	$contents = preg_replace('/<key><\!\[CDATA\[(.+?)\]\]><\/key>/s',
		 			'<key><![CDATA[' . $encryptor->exportKeys() . ']]></key>', $contents
		 	);
		 	
	 		file_put_contents($file, $contents);
			
		 	Mage::app()->cleanCache();
			
		 	echo "\nPlease refer to the application's local.xml file for your new key\n";
	 	} else {
	 		echo 'This version of Magento is not Enterprise' . "\n";
	 		
	 	}
    }
    
    /**
     * Resets the encrypted configurations as well as all of the encrypted data in the other 
     * tables you define.
     *
     * Command: wiz encrypt-resetData "sales/quote_payment|payment_id|cc_number_enc" "sales/quote_payment|payment_id|cc_cid_enc" "sales/order_payment|entity_id|cc_number_enc"
     * The pipe delimeted fields are:
     * 		table alias
     * 		primary key
     * 		encrypted column
     *
     * @author Ben Robie <brobie@gmail.com>
     */
    function resetDataAction($options) {
    	Wiz::getMagento();
	 	if (Mage::getConfig()->getModuleConfig('Enterprise_Pci')){
    		require 'enterprise/Enterprise.php';
    
    		$changeEncryption = new Encryption_Change($options);
			$changeEncryption->echoConfigPaths();
    		$changeEncryption->reEncryptDatabaseValues(false);
    
    	} else {
    		echo 'This version of Magento is not Enterprise' . "\n";
    	}
    	 
    }
    
  
    /**
     * Encrypts and stores a given value into the core_config_data table. After re-encryption is done
     * you can test that it worked with the "wiz encrypt-decryptTestValue" command.
     *
     * Command: wiz encrypt-encryptTestValue valuetoencrypt
     *
     * @author Ben Robie <brobie@gmail.com>
     */
    function encryptTestValueAction($options) {
    	Wiz::getMagento();
    	$encryptedValue = Mage::helper('core')->encrypt($options[0]);
    	$config = Mage::getResourceModel('core/config');
    	$config->saveConfig('wiz/encrypt/test', $encryptedValue, 'default', 0);
    	Mage::app()->cleanCache();
    }

    /**
     * Decrypts and echos out the encrypted value sent in by the "wiz encrypt-encryptTestValue" command.
     *
     * Command: wiz encrypt-decryptTestValue
     *
     * @author Ben Robie <brobie@gmail.com>
     */
    function decryptTestValueAction($options) {
    	Wiz::getMagento();
    	$encryptedValue = Mage::getStoreConfig('wiz/encrypt/test');
    	$decryptedValue = Mage::helper('core')->decrypt($encryptedValue);
    	$output[] = array('Descripted Value' => $decryptedValue);
		echo Wiz::tableOutput($output);
    }
    
  }

