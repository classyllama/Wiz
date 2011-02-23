<?php

Class Wiz_Plugin_Magento extends Wiz_Plugin_Abstract {

    /**
     * Returns the version of Magento that Wiz is currently pointing to.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function versionAction() {
        Wiz::getMagento();
        echo Mage::getVersion().PHP_EOL;
        return TRUE;
    }
}