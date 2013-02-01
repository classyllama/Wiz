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
		
		foreach($this->options as $index => $info) {
			$tableInfo = explode('|', $info);
			$table = $this->getTable($tableInfo[0]);
			$select = $this->_getWriteAdapter()->select()->from($table, array(
					$tableInfo[1],
					$tableInfo[2] ));
			
			$attributeValues = $this->_getWriteAdapter()->fetchPairs($select);
			foreach($attributeValues as $valueId=>$value) {
				if ($value){
					$this->_getWriteAdapter()->update($table, array(
							$tableInfo[2] => $this->_encryptor->encrypt($this->_encryptor->decrypt($value)) ), array(
							$tableInfo[1] . ' = ?' => (int) $valueId ));
				}
			}
		}
	}
}