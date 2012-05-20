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

class Wiz_Plugin_Cron extends Wiz_Plugin_Abstract
{
    /**
     * Returns a list of registered crontab jobs.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     **/
    public function jobsAction() {
        $modelMapping = array();
        Wiz::getMagento();

        $modelMapping = $this->getCrontabJobs();

        echo Wiz::tableOutput($modelMapping);
    }

    /**
     * Lists the event listeners that will execute when cron runs.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function listenersAction() {
        $modelMapping = array();
        Wiz::getMagento();

        $modelMapping = $this->getCrontabEvents();

        echo Wiz::tableOutput($modelMapping);
    }

    /**
     * Runs the cron.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function runAction($options) {
        Wiz::getMagento();

        Mage::app('admin')->setUseSessionInUrl(false);

        try {
            Mage::getConfig()->init()->loadEventObservers('crontab');
            Mage::app()->addEventArea('crontab');
            Mage::dispatchEvent('default');
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * returns a list of observers from the configuration XML from
     * a config path.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function getCrontabEvents() {
        $config = Mage::getConfig();
        foreach ($config->getNode("crontab/events")->children() as $childName => $observerInfo) {
            foreach ($observerInfo->observers->children() as $parent => $data) {
                $modelMapping[] = array(
                    'Name' => $childName,
                    'ID' => $parent,
                    'Class::Method' => $data->class . '::' . (string)$data->method,
                );
            }
        }
        return $modelMapping;
    }

    /**
     * returns a list of observers from the configuration XML from
     * a config path.
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    function getCrontabJobs() {
        $config = Mage::getConfig();

        foreach (array('crontab/jobs', 'default/crontab/jobs') as $path) {
            foreach ($config->getNode($path)->children() as $childName => $observerInfo) {
                $cronConfig = (string)$observerInfo->schedule->config_path ? (string)$observerInfo->schedule->config_path : (string)$observerInfo->schedule->cron_expr;
                $modelMapping[] = array(
                    'Name' => $childName,
                    'Schedule' => $cronConfig ? $cronConfig : '<none>',
                    'Run' => $observerInfo->run->model,
                );
            }
        }

        return $modelMapping;
    }
}
