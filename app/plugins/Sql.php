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
 * SQL Plugin for Wiz
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
class Wiz_Plugin_Sql extends Wiz_Plugin_Abstract {

    private function _getDbConfig($config = 'core') {
        Wiz::getMagento();
        $resources = Mage::getSingleton('core/resource');
        $connection = $resources->getConnection('core');
        $config = $connection->getConfig();
        return $config;
    }

    /**
     * Returns information about the database resource connection.
     *
     * @param database connection to use (default: 'core')
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function infoAction($options) {
        $config = $this->_getDbConfig();
        foreach ($config as $key => $value) {
            if (!$value) continue;
            if (is_array($value)) {
                $value = var_export($value, TRUE);
            }
            echo $key . ' = '. $value.PHP_EOL;
        }
        echo 'MySQL command line: '."mysql -u{$config['username']} -p{$config['password']}".( $config['port'] ? " -P{$config['port']}" : '')." -h{$config['host']} {$config['dbname']}".PHP_EOL;
    }

    /**
     * Opens up a shell command directly to the the database server.
     *
     * @param string $options 
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function cliAction($options) {
        $config = $this->_getDbConfig();
        proc_close(proc_open("mysql -u{$config['username']} -p{$config['password']}".( $config['port'] ? " -P{$config['port']}" : '')." -h{$config['host']} {$config['dbname']}", array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));
    }

    /**
     * Executes a query against the database.  You must enclose the query in single-quotes.
     * You can optionally specify MySQL's batch mode by adding the word batch after the
     * query.  Ensure not to add a semi-colon after the end of the query otherwise it
     * will think you meant to end the bash command.
     *
     * Example: sql-exec 'select * from core_resource' batch
     *
     * @param SQL Query
     * @param batch (optionally specifies batch mode)
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function execAction($options) {
        $query = mysql_real_escape_string(array_shift($options));
        $config = $this->_getDbConfig();
        if (count($options) > 0 && $options[0] == 'batch') {
            $batch = '--batch';
        }
        proc_close(proc_open("mysql $batch -u{$config['username']} -p{$config['password']} -P{$config['port']} -h{$config['host']} {$config['dbname']} -e \"$query\"", array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));
    }
}
