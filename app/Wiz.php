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

ini_set('date.timezone', 'America/Chicago');
error_reporting(-1);
require 'lib/Wiz/SimpleXmlElement.php';
ini_set('display_errors', 1);

define('WIZ_DS', DIRECTORY_SEPARATOR);

/**
 * Primary Wiz class.  Sets up the application and gets everything going.  okay?
 *
 * @package default
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
class Wiz {

    const WIZ_VERSION = '0.9.8';

    static private $config;

    public static function getWiz() {
        static $_wiz;
        if (!is_object($_wiz)) {
            $_wiz = new Wiz();
        }
        return $_wiz;
    }

    public static function getVersion() {
        return Wiz::WIZ_VERSION;
    }

    function __construct($args = null) {
        $this->pluginDirectory = dirname(__FILE__). WIZ_DS . 'plugins';
        $this->_findPlugins();
        $this->_parseArgs();
    }

    static function getUserConfig() {
        $options = array();
        if (file_exists($_SERVER['HOME'].'/.wizrc')) {
                    parse_str(strtr(file_get_contents($_SERVER['HOME'].'/.wizrc'), PHP_EOL, '&'), $options);
        }
        return $options;
    }

    static function setUserConfig($options) {
        file_put_contents(realpath($_SERVER['HOME'].'/.wizrc'), rawurldecode(http_build_query($options, '', PHP_EOL)));
    }

    public static function getConfigPath($path) {
        return (string)self::getConfig()->descend($path);
    }

    public static function getConfig() {
        if (empty(self::$config)) {
            $potentialFilesToLoad = array(
                dirname(__FILE__) . WIZ_DS . 'config.xml',
                dirname(__FILE__) . WIZ_DS . '..' . WIZ_DS . 'config.xml',
                $_SERVER['HOME'] . WIZ_DS . '.wiz.xml',
            );

            self::$config = simplexml_load_string('<config />', 'Wiz_Simplexml_Element');
            foreach ($potentialFilesToLoad as $filePath) {
                if (file_exists($filePath)) {
                    self::$config->extendByFile($filePath);
                }
            }
        }

        return self::$config;
    }

    static function saveConfig() {
        file_put_contents($_SERVER['HOME'] . WIZ_DS . '.wiz.xml', '<?xml version="1.0"?>'.PHP_EOL.Wiz::getConfig()->asNiceXml());
    }

    static function getMagentoRoot() {
        static $magentoRoot = FALSE;
        if ($magentoRoot === FALSE) {
            $wizMagentoRoot = array_key_exists('WIZ_MAGE_ROOT', $_ENV) ? $_ENV['WIZ_MAGE_ROOT'] : getcwd();

            // Run through all of the options until either we find something, or we've run out of places to look.
            do {
                $magePhpPath = $wizMagentoRoot . WIZ_DS . 'app' . WIZ_DS . 'Mage.php';
                if ($magePhpIsNotFound = !is_readable($magePhpPath)) {
                    $wizMagentoRoot = substr($wizMagentoRoot, 0, strrpos($wizMagentoRoot, WIZ_DS));
                }
            } while ($magePhpIsNotFound && strlen($wizMagentoRoot));

            if ($magePhpIsNotFound) {
                $wizMagentoRoot = FALSE;
            }
        }
        return $wizMagentoRoot;
    }

    /**
     * Instantiates and sets up Magento.  By default, use the admin scopeCode so we run
     * inside of the administration context.
     *
     * @param string $scopeCode 
     * @param string $scopeId 
     * @return Mage_Core_Model_App
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public static function getMagento($scopeCode = 'admin', $scopeId = 'store') {

        /**
         * Our local copy of the Magento Application Object
         * 
         * @see Mage_Core_Model_App
         */
        static $_magento;

        if (!$_magento) {
            // Did we get a directory from an environment variable?
            $wizMagentoRoot = Wiz::getMagentoRoot();

            // No dice. :-(
            if ($wizMagentoRoot === FALSE) {
                die ('Please specify a Magento root directory by setting WIZ_MAGE_ROOT.'.PHP_EOL);
            }

            chdir($wizMagentoRoot);

            /**
             * Attempt to bootstrap Magento.
             */
            $compilerConfig = 'includes/config.php';
            if (file_exists($compilerConfig)) {
                include $compilerConfig;
            }

            require 'app/Mage.php';

            umask(0);

            // If someone passes a scope code via he CLI, then use that.
            foreach (array('store', 'website') as $scope) {
                if (($argScopeCode = Wiz::getWiz()->getArg($scope)) !== FALSE) {
                    // If --store is specified, but not provided, use the default.
                    $scopeCode = $argScopeCode === TRUE ? '' : $argScopeCode;
                    $scopeId = $scope;
                    break;
                }
            }

            // We only want to enable profiling if it has been turned on within the
            // configuration AND if the --profile argument was passed into the command. 
            if(Mage::getStoreConfig('dev/debug/profiler') && Wiz::getWiz()->getArg('profile')){
            	Varien_Profiler::enable();
            }
            
            $_magento = Mage::app($scopeCode, $scopeId);
        }
        return $_magento;
    }

    public function versionAction() {
        echo 'Version: '.Wiz::WIZ_VERSION;
    }

    public function updateAction() {

        $latestVersion = file_get_contents('http://wizcli.com/latest-version');
        echo 'Current Wiz Version: ' . self::WIZ_VERSION . PHP_EOL;
        if (version_compare(self::WIZ_VERSION, $latestVersion, 'lt')) {
            // There is an upgrade available.
            echo 'Latest Wiz Version: ' . $latestVersion . PHP_EOL;
            echo 'An upgrade is available.' . PHP_EOL;
            while (1) {
                echo 'Do you wish to upgrade? [y,n] ';
                $input = strtolower(trim(fgets(STDIN)));
                if (in_array(strtolower($input), array('y', 'n'))) {
                    break;
                }
            }
            if ($input == 'n') {
                echo 'Upgrade cancelled.' . PHP_EOL;
            }
            else {
                // Aw snap, it's on now!
                $ourDirectory = dirname(dirname(__FILE__));
                
                // Do a quick sanity check to ensure that we "own" the directory.
                $objectsInOurDirectory = scandir(dirname(dirname(__FILE__)));

                // Only enable auto-upgrade if we have our own directory.  This will leave some 
                // people out, but for now it appears most people have it in ~/bin.
                if (count($objectsInOurDirectory) <= 7 
                  && in_array('wiz.php', $objectsInOurDirectory)
                  && in_array('app', $objectsInOurDirectory)
                  && in_array('wiz', $objectsInOurDirectory)) {
                    $parentDir = dirname($ourDirectory);
                    // Then it looks like it is ours... we can go ahead and do the upgrade.
                    if (is_writable($parentDir)) {
                        require 'lib/PEAR.php';
                        require 'lib/Archive/Tar.php';
                        $latestVersionFile = new Archive_Tar('http://wizcli.com/files/wiz-latest.tgz');
                        // If the file loaded properly, extract the contents over the current directory.
                        if (count($latestVersionFile->listContent()) > 0) {
                            $cwd = getcwd();
                            chdir($parentDir);
                            echo 'Removing current directory.' . PHP_EOL;
                            $this->rrmdir($ourDirectory);
                            echo 'Extracting files...';
                            $latestVersionFile->extract($parentDir);
                            echo 'done.' . PHP_EOL;
                        }
                        else {
                            echo 'There was a problem downloading the latest version.  Unable to continue.' . PHP_EOL;
                        }
                    }
                }
            }
        }
        else {
            echo 'You are already running the latest version.' . PHP_EOL;
        }

        // Check the remote service to see what the latest version of Wiz is.
    }

    private function rrmdir($dir) { 
        if (is_dir($dir)) { 
            $objects = scandir($dir); 
            foreach ($objects as $object) { 
                if ($object != "." && $object != "..") { 
                    if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object); 
                } 
            } 
            reset($objects); 
            rmdir($dir);
        } 
    }

    private function _findPlugins() {

        $plugins = array();
        $pluginFiles = new DirectoryIterator($this->pluginDirectory);
        
        foreach ($pluginFiles as $file) {
            $fileExtension = substr($file->getFilename(), -3);
            if ($file->isFile() && $fileExtension == "php") {
                require($file->getPathname());
                $plugins[] = basename($file->getFilename(), '.php');
            }
        }

        foreach ($plugins as $pluginName) {
            $pluginClass = 'Wiz_Plugin_' . $pluginName;
            $pluginInstance = new $pluginClass();
            foreach ($pluginInstance->getActions() as $action) {
                $this->_availableCommands[strtolower($pluginName).'-'.$action] = array(
                    'class' => $pluginClass,
                    'method' => $action.'Action'
                );
            }
        }
        $this->_availableCommands['command-list'] = array(
            'class' => 'Wiz',
            'method' => 'listActions'
        );
        $this->_availableCommands['help'] = array(
            'class' => 'Wiz',
            'method' => 'helpAction'
        );
        $this->_availableCommands['update'] = array(
            'class' => 'Wiz',
            'method' => 'updateAction'
        );
        foreach ($this->_availableCommands as $commandName => $commandArray) {
            $functionInfo = new ReflectionMethod($commandArray['class'], $commandArray['method']);
            $comment = $functionInfo->getDocComment();
            if ($comment) {
                $comment = preg_replace('#^\s+\* ?#m', '', substr($comment, 3, -2));
                $this->_availableCommands[$commandName]['documentation'] = $comment;
            }
        }
    }

    /**
     * Gives you help on a command.  For simplicity's sake, it just returns the Comment
     * block in the source code.
     *
     * @param string Command to get help on. 
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function helpAction($options) {
        $command = array_shift($options);

        if ($command == '') $command = 'help';

        if (array_key_exists($command, $this->_availableCommands)) {
            if (array_key_exists('documentation', $this->_availableCommands[$command])) {
                echo "Help for $command:".PHP_EOL.PHP_EOL;
                echo $this->_availableCommands[$command]['documentation'].PHP_EOL;
            }
            else {
                echo "No help available for: $command".PHP_EOL;
            }
        }
        else {
            echo "Unknown command: $command".PHP_EOL;
        }
    }

    /**
     * List all available actions that Wiz can perform.
     *
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function listActions() {
        echo PHP_EOL;
        echo 'Available commands: '.PHP_EOL;
        echo PHP_EOL;
        foreach ($this->_availableCommands as $commandName => $commandArray) {
            if(!array_key_exists('documentation', $commandArray) || trim($commandArray['documentation']) == '') {
                continue;
            }
            $docLines = explode(PHP_EOL, $commandArray['documentation']);
            $docLineOne = array_shift($docLines);
            if (($endOfSentence = strpos($docLineOne, '.')) !== FALSE) {
                $docLineOne = substr($docLineOne, 0, $endOfSentence + 1);
            }
            printf('  %-23s %s' . PHP_EOL, $commandName, $docLineOne);
        }
        echo PHP_EOL;
    }

    function _initWiz() {
        // Look for a configuration in .wizrc
        if (file_exists('~/.wizrc')) {
            $configurationOptions = file_get_contents('~/.wizrc');
        }
    }

    public function run() {
        $argv = $_SERVER['argv'];

        // If the first item is us.  We don't need it.
        if (count($argv) == 1 && $argv[0] == dirname(dirname(__FILE__)) . WIZ_DS . 'wiz.php') {
            array_shift($argv);
        }

        // Next item is the command
        $command = array_shift($argv);

        // Attempt to run the command.
        if (array_key_exists($command, $this->_availableCommands)) {
            if ($this->_availableCommands[$command]['class'] == __CLASS__) {
                $pluginInstance = $this;
            }
            else {
                $pluginInstance = new $this->_availableCommands[$command]['class']();
            }
            try {
                $pluginInstance->{$this->_availableCommands[$command]['method']}($argv);
            } catch(Exception $e) {
                echo 'An error occured while processing the command: ' . $command . PHP_EOL;
                echo $e->getMessage() . PHP_EOL;
            }
        }
        elseif ($command == '') {
            echo $this->getHelp();
        }
        else {
            echo 'Unable to find that command: ' . $command . PHP_EOL;
        }
    }

    public function getHelp() {
        $helpText = 'Wiz v'.Wiz::WIZ_VERSION.PHP_EOL;
        $helpText .= 'Provides a CLI interface to get information from, script, and help you manage'.PHP_EOL;
        $helpText .= 'your Magento installation.'.PHP_EOL;
        $helpText .= PHP_EOL;
        $helpText .= 'Usage:';
        $helpText .= PHP_EOL;
        $helpText .= '  wiz [global-options] <command> [command-options]';
        $helpText .= PHP_EOL;
        $helpText .= '                Runs a command.';
        $helpText .= PHP_EOL;
        $helpText .= PHP_EOL;
        $helpText .= '  wiz help <command>';
        $helpText .= PHP_EOL;
        $helpText .= '                Returns help on a command.';
        $helpText .= PHP_EOL;
        $helpText .= PHP_EOL;
        $helpText .= '  wiz command-list';
        $helpText .= PHP_EOL;
        $helpText .= '                Returns the list of available commands.';
        $helpText .= PHP_EOL;
        $helpText .= PHP_EOL;
        $helpText .= 'Global Options:';
        $helpText .= PHP_EOL;
        $helpText .= '  --batch [csv|pipe|tab]';
        $helpText .= PHP_EOL;
        $helpText .= '                Returns tabular data in a parseable format.  Defaults to "csv"';
        $helpText .= PHP_EOL;
        $helpText .= PHP_EOL;
        $helpText .= '  --store <store-code|store-id>,';
        $helpText .= PHP_EOL;
        $helpText .= '  --website <website-code|website-id>';
        $helpText .= PHP_EOL;
        $helpText .= '                Executes Magento as this particular store or website.';
        $helpText .= PHP_EOL;
        $helpText .= PHP_EOL;
        return $helpText . PHP_EOL;
    }

    public static function inspect() {
        $args = func_get_args();
        call_user_func_array('Wiz_Inspector::inspect', $args);
    }

    /**
     * Parse input arguments, removing them as we find them.
     *
     * @return Wiz_Plugin_Abstract
     */
    protected function _parseArgs() {
        $current = $commandStart = $inCommand = false;
        $commandEnd = 1;

        foreach ($_SERVER['argv'] as $position => $arg) {

            if ($commandStart === FALSE && array_key_exists($arg, $this->_availableCommands)) {
                $commandStart = $position;
                $inCommand = TRUE;
            }

            $match = array();
            if (preg_match('#^--([\w\d_-]{1,})$#', $arg, $match) || preg_match('#^-([\w\d_]{1,})$#', $arg, $match)) {
                $inCommand = false;
                $current = $match[1];
                $this->_args[$current] = true;
            } else {
                if ($current) {
                    $this->_args[$current] = $arg;
                } else if (!$inCommand && preg_match('#^([\w\d_]{1,})$#', $arg, $match)) {
                    $this->_args[$match[1]] = true;
                } else {
                    if ($inCommand && $commandStart != $position) {
                        $commandEnd++;
                    }
                }
            }
        }
        $_SERVER['argv'] = array_slice($_SERVER['argv'], $commandStart, $commandEnd);
        // var_dump($commandStart, $commandEnd);
        // var_dump($_SERVER['argv']);
        // var_dump($this->_args);
        return $this;
    }

    /**
     * Retrieve argument value by name or the default specified
     *
     * @param string $name the argument name
     * @return mixed
     */
    public function getArg($name, $default = false) {
        if (isset($this->_args[$name])) {
            return $this->_args[$name];
        }
        return $default;
    }

    /**
     * Modified version of the code at the site below:
     * @see http://www.pyrosoft.co.uk/blog/2007/07/01/php-array-to-text-table-function/
     */
    public static function tableOutput($table) {
        if (Wiz::getWiz()->getArg('batch')) {
            return Wiz::batchOutput($table);
        }
        else {
            return Wiz::prettyTableOutput($table);
        }
    }

    public static function batchOutput($table) {
        $format = Wiz::getWiz()->getArg('batch');
        if (!is_array($table) || count($table) < 1 || !is_array($table[0])) {
            $table = array(array('Result' => 'No Data'));
        }

        $keys = array_keys($table[0]);
        $delimiter = $enclosure = '"';

        array_unshift($table, $keys);

        switch ($format) {
            case 'csv':
            default:
                $delimiter = ',';
                $enclosure = '"';
                // Quickly put everything 
                break;
            case 'pipe':
                $delimiter = '|';
                break;
            case 'tab':
                $delimiter = "\t";
                break;
        }

        // We use some memory here to quickly create a CSV file.
        $csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');
        foreach ($table as $row) {
            fputcsv($csv, $row, $delimiter, $enclosure);
        }
        rewind($csv);
        $output = stream_get_contents($csv);
        return $output;
    }

    public static function prettyTableOutput($table) {
        if (!is_array($table) || count($table) < 1 || !is_array($table[0])) {
            $table = array(array('Result' => 'No Data'));
        }
        $keys = array_keys($table[0]);
        array_push($table, array_combine($keys, $keys));
        foreach ($table AS $row) {
            $cell_count = 0;
            foreach ($row AS $key=>$cell) {
                $cell_length = strlen($cell);
                $cell_count++;
                if (!isset($cell_lengths[$key]) || $cell_length > $cell_lengths[$key]) $cell_lengths[$key] = $cell_length;
            }    
        }
        array_pop($table);

        // Build header bar
        $bar = '+';
        $header = '|';
        $i=0;

        foreach ($cell_lengths AS $fieldname => $length) {
            $i++;
            $bar .= str_pad('', $length+2, '-')."+";

            $name = $fieldname;
            if (strlen($name) > $length) {
                // crop long headings

                $name = substr($name, 0, $length-1);
            }
            $header .= ' '.str_pad($name, $length, ' ', STR_PAD_RIGHT) . " |";

        }

        $output = '';

        $output .= $bar."\n";
        $output .= $header."\n";

        $output .= $bar."\n";

        // Draw rows

        foreach ($table AS $row) {
            $output .= "|";

            if (is_array($row)) {
                foreach ($row AS $key=>$cell) {
                    $output .= ' '.str_pad($cell, $cell_lengths[$key], ' ', STR_PAD_RIGHT) . " |";
                }
            }
            else {
                foreach ($cell_lengths AS $key=>$length) {
                    $output .= str_repeat($row, $length+2) . '|';
                }
            }
            $output .= "\n";
        }

        $output .= $bar."\n";
        return $output;
    }
}

// This is probably not the best place for this, but it works for now.
class Wiz_Plugin_Abstract {
    /**
     * Input arguments
     *
     * @var array
     */
    protected $_args        = array();

    /**
     * Initialize application with code (store, website code)
     *
     * @var string
     */
    protected $_appCode     = 'admin';

    /**
     * Initialize application code type (store, website, store_group)
     *
     * @var string
     */
    protected $_appType     = 'store';

    /**
     * Constructor
     *
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function __construct() {
        // $this->_parseArgs();
    }

    /**
     * Returns a list of actions that this plugin contains.
     *
     * @return array
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function getActions() {
        $reflector = new ReflectionClass($this);
        foreach ($reflector->getMethods() as $reflectionMethod) {
            if (($commandLength = strpos($reflectionMethod->name, 'Action')) !== FALSE &&
                ($commandLength + 6) == strlen($reflectionMethod->name)) {
                $_commands[] = substr($reflectionMethod->name, 0, $commandLength);
            }
        }
        return $_commands;
    }
}

class Wiz_Inspector {
    public static function inspect() {
        $args = func_get_args();
        echo __METHOD__.PHP_EOL;
        $arg = $args[0];
        if (is_object($arg)) {
            echo 'Hierarchy:'.PHP_EOL;
            implode(','.PHP_EOL, self::getParents($arg));
            // var_dump($args);
        }
    }

    public static function getParents($object) {
        $class = new ReflectionClass($object);
        $parents = array();
        $a = 0;

        while ($parent = $class->getParentClass()) {
            if ($a++ == 5)
                break;
            // var_dump($parent);
            $parents[] = $parent->getName();
            // var_dump($parents);
        }
        return $parents;
    }

    public static function getMethods($object) {
        $a = new ReflectionClass($object);
        foreach ($a->getMethods() as $method) {
            var_dump($method);
            echo $method->name.PHP_EOL;
        }
        echo PHP_EOL.PHP_EOL;
    }
}
