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

class Wiz_Plugin_Store extends Wiz_Plugin_Abstract {

    /**
     * Lists all of the stores, store codes, and websites on the magento installation.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function listAction() {
        Wiz::getMagento();
        $storeCollection = Mage::getModel('core/store')->getCollection();
        foreach ($storeCollection as $store) {
            $rows[] = array(
                'Website (Id)' => $store->getWebsite()->getCode().' ('.$store->getWebsiteId().')'. ($store->getWebsite()->getIsDefault() ? /*" \033[1;37;40m*/' default'/*\033[0m" */: ''),
                'Group (Id)' => $store->getGroup()->getName().' ('.$store->getGroup()->getId().')',
                'Code (Id)' => $store->getName() . ' ('.$store->getCode().')',
                'Active' => $store->getIsActive(),
            );
        }
        echo Wiz::tableOutput($rows);
    }
}
