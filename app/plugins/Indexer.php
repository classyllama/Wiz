<?php

class Wiz_Plugin_Indexer extends Wiz_Plugin_Abstract {


    /**
     * Get Indexer instance
     *
     * @return Mage_Index_Model_Indexer
     */
    protected function _getIndexer()
    {
        return Mage::getSingleton('index/indexer');
    }

    /**
     * Parse string with indexers and return array of indexer instances
     *
     * @param string $string
     * @return array
     */
    protected function _parseIndexerString($string)
    {
        $processes = array();
        if ($string == 'all') {
            $collection = $this->_getIndexer()->getProcessesCollection();
            foreach ($collection as $process) {
                $processes[] = $process;
            }
        } else if (!empty($string)) {
            $codes = explode(',', $string);
            foreach ($codes as $code) {
                $process = $this->_getIndexer()->getProcessByCode(trim($code));
                if (!$process) {
                    echo 'Warning: Unknown indexer with code ' . trim($code) . "\n";
                } else {
                    $processes[] = $process;
                }
            }
        }
        return $processes;
    }

    /**
     * Displays the status of the specified indexing processes.
     * If no processes are specified, all will be shown.
     *
     * @return status of index processes
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function statusAction($options) {
        Wiz::getMagento();

        $processes = $this->_parseIndexerString(count($options) == 0 ? 'all' : implode(',', $options));

        foreach ($processes as $process) {
            $row = array();
            /* @var $process Mage_Index_Model_Process */

            $status = 'Unknown';
            switch ($process->getStatus()) {
                case Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX:
                    $status = 'Require Reindex';
                    break;

                case Mage_Index_Model_Process::STATUS_RUNNING:
                    $status = 'Processing';
                    break;

                case Mage_Index_Model_Process::STATUS_PENDING:
                    $status = 'Ready';
                    break;
            }

            $mode = 'Unknown';
            switch ($process->getMode()) {
                case Mage_Index_Model_Process::MODE_REAL_TIME:
                    $mode = 'Update on Save';
                    break;
                case Mage_Index_Model_Process::MODE_MANUAL:
                    $mode = 'Manual Update';
                    break;
            }

            $row['Name (code)'] = $process->getIndexer()->getName().' ('.$process->getIndexerCode().')';
            $row['Status'] = $status;
            $row['Mode'] = $mode;

            $rows[] = $row;
        }
        echo Wiz::tableOutput($rows);
    }

    /**
     * Reindexes the specified processes. 
     *
     * @param processes to reindex
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function reindexAction($options) {
        Wiz::getMagento();
        $processes = $this->_parseIndexerString(implode(',', $options));

        if (count($processes) == 0) {
            echo 'Please specify a process to reindex.  Either "all" or run indexer-status for list.' . PHP_EOL;
        }

        foreach ($processes as $process) {
            /* @var $process Mage_Index_Model_Process */
            try {
                echo 'Reindexing '.$process->getIndexer()->getName().'...';
                $process->reindexEverything();
                echo  'done.'.PHP_EOL;
            } catch (Mage_Core_Exception $e) {
                echo  'exception.'.PHP_EOL;
                echo $e->getMessage() . PHP_EOL;
            } catch (Exception $e) {
                echo  'exception.'.PHP_EOL;
                echo $process->getIndexer()->getName() . " index process unknown error:\n";
                echo $e . PHP_EOL;
            }
        }
    }

    private function _setMode($processes, $mode) {
        $updated = array();

        foreach ($processes as $process) {
            /* @var $process Mage_Index_Model_Process */
            try {
                $process->setMode($mode)->save();
                $updated[] = $process->getIndexer()->getName();
            } catch (Mage_Core_Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            } catch (Exception $e) {
                echo $process->getIndexer()->getName() . " index process unknown error:\n";
                echo $e . PHP_EOL;
            }
        }
        return $updated;
    }

    /**
     * Changes the status of the indexing processes to "Update on Save".
     * If no processes are specified, all processes will be set.
     *
     * @param processes to index
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function realtimeAction($options) {
        Wiz::getMagento();
        $processes = $this->_parseIndexerString(count($options) == 0 ? 'all' : implode(',', $options));
        $updated  = $this->_setMode($processes, Mage_Index_Model_Process::MODE_REAL_TIME);
        echo 'Index' . (count($updated) > 1 ? 'es' : '') . ' set to Update on Save: ' . implode(', ', $updated) . PHP_EOL;
    }

    /**
     * Changes the status of the indexing processes to "Manual".
     * If no processes are specified, all processes will be set.
     *
     * @param processes to index
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function manualAction($options) {
        Wiz::getMagento();
        $processes = $this->_parseIndexerString(count($options) == 0 ? 'all' : implode(',', $options));
        $updated = $this->_setMode($processes, Mage_Index_Model_Process::MODE_MANUAL);
        echo 'Index' . (count($updated) > 1 ? 'es' : '') . ' set to Manual: ' . implode(', ', $updated) . PHP_EOL;
    }
}