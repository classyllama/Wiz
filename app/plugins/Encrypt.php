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
    	if (class_exists(Enterprise_Pci_Model_Resource_Key_Change)){
    		require 'enterprise/Enterprise.php';
    
    		$changeEncryption = new Encryption_Change($options);
			$changeEncryption->echoConfigPaths();
    		$changeEncryption->reEncryptDatabaseValues(false);
    
    	} else {
    		echo 'This version of Magento is not Enterprise' . "\n";
    	}
    	 
    }
    
    /**
     * Encrypts the data keys with a master key file.
     *
     * Command: wiz encrypt-encryptDataKeys
     *
     * @author Ben Robie <brobie@gmail.com>
     */
    function encryptDataKeysAction($options) {
    	Wiz::getMagento();
    	if (class_exists(Cds_Pci_Model_Data_Encryption_Key)){
			$keyEncryption = Mage::getModel('cds_pci/data_encryption_key');
    		if ($keyEncryption){
    			if ($options[0] == 'force'){
    				$keyEncryption->encryptDataEncryptionKeys(true);
    			} else {
    				$keyEncryption->encryptDataEncryptionKeys();
    			}
    		}
    	} else {
    		echo 'This version of Magento is not eHub' . "\n";
    	}
    
    }
    
    function encryptTestValueAction($options) {
    	Wiz::getMagento();
    	$encryptedValue = Mage::helper('core')->encrypt($options[0]);
    	$config = Mage::getResourceModel('core/config');
    	$config->saveConfig('wiz/encrypt/test', $encryptedValue, 'default', 0);
    	Mage::app()->cleanCache();
    }

    function decryptTestValueAction($options) {
    	Wiz::getMagento();
    	$encryptedValue = Mage::getStoreConfig('wiz/encrypt/test');
    	$decryptedValue = Mage::helper('core')->decrypt($encryptedValue);
    	echo $decryptedValue;
    }
    
  }

