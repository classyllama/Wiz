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
 * An extention to the Enterprise_Pci_Model_Resource_Key_Change class that allows for the re-encryption
 * of more than just the one table listed in Enterprise_Pci_Model_Resource_Key_Change.
 *
 * @author Ben Robie <brobie@gmail.com>
 */

class Encryption_Change extends Enterprise_Pci_Model_Resource_Key_Change {
	
	protected $options = array();
	
	public function __construct($options){
		parent::__construct();
		$this->options = $options;
	}

	/**
	 * Gather saved credit card numbers (or other encrypted data) from the given tables 
	 * and re-encrypt them.
	 *
	 * @author Ben Robie <brobie@gmail.com>
	 */
	protected function _reEncryptCreditCardNumbers() {
		$output = array();
		
		foreach($this->options as $index => $info) {
			$tableInfo = explode('|', $info);
			$table = $this->getTable($tableInfo[0]);
			$select = $this->_getWriteAdapter()->select()->from($table, array(
					$tableInfo[1],
					$tableInfo[2] ));
			
			$attributeValues = $this->_getWriteAdapter()->fetchPairs($select);
			$counts = array();
			$count = 0;
			foreach($attributeValues as $valueId=>$value) {
				if ($value){
					$count++;
					$this->_getWriteAdapter()->update($table, array(
							$tableInfo[2] => $this->_encryptor->encrypt($this->_encryptor->decrypt($value)) ), array(
							$tableInfo[1] . ' = ?' => (int) $valueId ));
				}
			}
			$output[] = array('table' => $table, 'column'=> $tableInfo[2], 'count'=> $count);
		}
		// Re-Encrypt the test config if it is there.
		$encryptedValue = Mage::getStoreConfig('wiz/encrypt/test');
		$reEncryptedValue = $this->_encryptor->encrypt($this->_encryptor->decrypt($encryptedValue));
		$config = Mage::getResourceModel('core/config');
		$config->saveConfig('wiz/encrypt/test', $reEncryptedValue, 'default', 0);
		
		echo Wiz::tableOutput($output);
	}
	
	/**
	 * Prints out all of the config paths that are marked as encrypted
	 *
	 * @author Ben Robie <brobie@gmail.com>
	 */
	public function echoConfigPaths(){
		$output = array();
		$paths = Mage::getSingleton('adminhtml/config')->getEncryptedNodeEntriesPaths();
		foreach ($paths as $path){
			$output[] = array('core_config_data path' => $path);
		}
		echo Wiz::tableOutput($output);
		
	}
}