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
 * @author     Ricardo Martins <ricardo@ricardomartins.info>
 * @copyright  Copyright (c) by 2012 Ricardo Martins
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Database Plugin for Wiz
 *
 * @author Ricardo Martins <ricardo@ricardomartins.info>
 */
Class Wiz_Plugin_Database extends Wiz_Plugin_Abstract {

    private $host;
    private $username;
    private $password;
    private $dbname;
    private $skipLogTables;
    private $objConfig;
    
    function _init(){
        Wiz::getMagento();
        $this->objConfig = Mage::getConfig()->getResourceConnectionConfig("default_setup");
    }

    function _askOrDefault($msg, $varname, $default=null){
        $defaultValue = (!is_null($default))?$default:(string)$this->objConfig->$varname;
        printf('%s (%s): ', $msg, $defaultValue);
        $input = trim(fgets(STDIN));
        $this->$varname = (empty($input))?$defaultValue:$input;        
    }
    
    function _humanFilesize($filename, $decimals = 2) {
        $bytes = filesize($filename);
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * Dumps the store database into a file.
     *
     * @author Ricardo Martins <ricardo@ricardomartins.info>
     */
    function exportAction(){
        $this->_init();
        // var_dump($config->host,$config->username, $config->password, $config->dbname);

        $this->_askOrDefault('DB Host','host');
        $this->_askOrDefault('DB User','username');
        $this->_askOrDefault('DB password','password');
        $this->_askOrDefault('DB name','dbname');
        $this->_askOrDefault('Skip log data?','skipLogTables','N');
        $this->_askOrDefault('Filename', 'filename', 'dump.sql');

        // mysqldump -h HOST -u USER -p --ignore-table=DBNAME.log_customer --ignore-table=DBNAME.log_visitor --ignore-table=DBNAME.log_visitor_info --ignore-table=DBNAME.log_url --ignore-table=DBNAME.log_url_info  --ignore-table=DBNAME.log_quote  --ignore-table=DBNAME.report_viewed_product_index  --ignore-table=DBNAME.report_compared_product_index  --ignore-table=DBNAME.report_event  --ignore-table=DBNAME.catalog_compare_item --opt DBNAME > FILE.sql
        $config = $this;
        $skipTablesCommand = '';
        if(strtolower($this->skipLogTables)!=='n'){
            $skipTablesCommand = "--ignore-table={$config->dbname}.log_customer --ignore-table={$config->dbname}.log_visitor --ignore-table={$config->dbname}.log_visitor_info --ignore-table={$config->dbname}.log_url --ignore-table={$config->dbname}.log_url_info  --ignore-table={$config->dbname}.log_quote  --ignore-table={$config->dbname}.report_viewed_product_index  --ignore-table={$config->dbname}.report_compared_product_index  --ignore-table={$config->dbname}.report_event  --ignore-table={$config->dbname}.catalog_compare_item";
        }

        $command = "mysqldump -h {$config->host} -u {$config->username} -p{$config->password} {$skipCommand} --opt {$config->dbname} > {$config->filename}";
        printf('Dump started...' . PHP_EOL);
        proc_close(proc_open($command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));

        if(!empty($skipTablesCommand)){
            printf('Appending log tables structure to file...'. PHP_EOL);
            $command = "mysqldump -h {$config->host} -u {$config->username} -p{$config->password} {$config->dbname} log_customer log_visitor log_visitor_info log_url log_url_info  log_quote  report_viewed_product_index  report_compared_product_index  report_event  catalog_compare_item --no-data   >> {$config->filename}";
            proc_close(proc_open($command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));
        }

        printf("----\nDump finished. \nFile: %s. \nSize: %s.\n", $config->filename, $this->_humanFilesize($config->filename));

        //Ask for compacting the file
        $this->_askOrDefault('Compact sql to .tar.gz file?', 'compact', 'N' );
        // tar czvf FILE.tar.gz FILE.sql
        if(strtolower($this->compact)!=='n'){
            proc_close(proc_open("tar czvf {$config->filename}.tar.gz {$config->filename}", array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));
            printf("File compacted to %s. Size: %s.\n", $config->filename . '.tar.gz', $this->_humanFilesize($config->filename . '.tar.gz'));
        }
    }
}