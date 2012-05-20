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
 * Log Plugin for Wiz - Code mostly borrowed from Magento's own shell scripts.
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
Class Wiz_Plugin_Log extends Wiz_Plugin_Abstract {
    /**
     * Retrieve Log instance
     *
     * @return Mage_Log_Model_Log
     */
    protected function _getLog() {
        if (is_null($this->_log)) {
            $this->_log = Mage::getModel('log/log');
        }
        return $this->_log;
    }

    /**
     * Convert count to human view
     *
     * @param int $number
     * @return string
     */
    protected function _humanCount($number) {
        if ($number < 1000) {
            return $number;
        } else if ($number >= 1000 && $number < 1000000) {
            return sprintf('%.2fK', $number / 1000);
        } else if ($number >= 1000000 && $number < 1000000000) {
            return sprintf('%.2fM', $number / 1000000);
        } else {
            return sprintf('%.2fB', $number / 1000000000);
        }
    }

    /**
     * Convert size to human view
     *
     * @param int $number
     * @return string
     */
    protected function _humanSize($number) {
        if ($number < 1000) {
            return sprintf('%d b', $number);
        } else if ($number >= 1000 && $number < 1000000) {
            return sprintf('%.2fKb', $number / 1000);
        } else if ($number >= 1000000 && $number < 1000000000) {
            return sprintf('%.2fMb', $number / 1000000);
        } else {
            return sprintf('%.2fGb', $number / 1000000000);
        }
    }

    /**
     * Displays statistics for each log table.
     * 
     * Adapted from the log.php that ships with Magento.
     * 
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function statusAction() {
        Wiz::getMagento();
        $resource = $this->_getLog()->getResource();
        $adapter  = $resource->getReadConnection();

        // log tables
        $tables = array(
            $resource->getTable('log/customer'),
            $resource->getTable('log/visitor'),
            $resource->getTable('log/visitor_info'),
            $resource->getTable('log/url_table'),
            $resource->getTable('log/url_info_table'),
            $resource->getTable('log/quote_table'),

            $resource->getTable('reports/viewed_product_index'),
            $resource->getTable('reports/compared_product_index'),
            $resource->getTable('reports/event'),

            $resource->getTable('catalog/compare_item'),
        );

        $rows        = 0;
        $dataLength   = 0;
        $indexLength = 0;
        $rowData = array();

        foreach ($tables as $table) {
            $query  = $adapter->quoteInto('SHOW TABLE STATUS LIKE ?', $table);
            $status = $adapter->fetchRow($query);

            if (!$status) {
                continue;
            }

            $rows += $status['Rows'];
            $dataLength += $status['Data_length'];
            $indexLength += $status['Index_length'];

            $rowData[] = array(
                'Table Name' => $table,
                'Rows' => $this->_humanCount($status['Rows']),
                'Data Size' => $this->_humanSize($status['Data_length']),
                'Index Size' => $this->_humanSize($status['Index_length'])
            );
        }

        $rowData[] = '-';

        $rowData[] = array(
            'Table Name' => 'Totals',
            'Rows' => $this->_humanCount($rows),
            'Data Size' => $this->_humanSize($dataLength),
            'Index Size' => $this->_humanSize($indexLength)
        );

        echo Wiz::tableOutput($rowData);
    }

    /**
     * Cleans Magento's database logs.  Uses Magento's global settings.
     * 
     * Adapted from the log.php that ships with Magento.
     * 
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function cleanAction() {
        Wiz::getMagento('');
        $this->_getLog()->clean();
        $savedDays = Mage::getStoreConfig(Mage_Log_Model_Log::XML_LOG_CLEAN_DAYS);
        echo "Log cleaned. Log days saved: $savedDays".PHP_EOL;
    }
}
