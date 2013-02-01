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
		if (class_exists(Enterprise_Pci_Model_Resource_Key_Change)){
			$newKey = Mage::getResourceSingleton('enterprise_pci/key_change')->changeEncryptionKey(null);
			Mage::app()->cleanCache();
			echo 'New Key: '. $newKey . "\n";
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
    	if (class_exists(Enterprise_Pci_Model_Resource_Key_Change)){
    		require 'enterprise/Enterprise.php';
    
    		$changeEncryption = new Encryption_Change($options);
    		$changeEncryption->reEncryptDatabaseValues(false);
    
    	} else {
    		echo 'This version of Magento is not Enterprise' . "\n";
    	}
    	 
    }
    
  }

