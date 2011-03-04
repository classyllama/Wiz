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

class Wiz_Plugin_Store extends Wiz_Plugin_Abstract {

    public static function listAction() {
        Wiz::getMagento();
        $storeCollection = Mage::getModel('core/store')->getCollection();
        foreach ($storeCollection as $store) {
            $rows[] = array(
                'store_id' => $store->getStoreId(),
                'website_id' => $store->getWebsiteId(),
                'code' => $store->getCode(),
                'name' => $store->getName(),
                'is_active' => $store->getIsActive(),
            );
        }
        echo Wiz::tableOutput($rows);
        return TRUE;
    }
}
