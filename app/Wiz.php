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

// For now, let's eschew extensive command line params in favor of environment variables.

ini_set('date.timezone', 'America/Chicago');
error_reporting(-1);
ini_set('display_errors', 1);

define('WIZ_DS', DIRECTORY_SEPARATOR);

/**
 * Primary Wiz class.  Sets up the application and gets everything going.  okay?
 *
 * @package default
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
class Wiz {

    const WIZ_VERSION = '0.1.0-alpha';

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

    public static function getPlugin($code) {
        
    }

    function __construct($args = null) {
        $this->pluginDirectory = dirname(__FILE__). WIZ_DS . 'plugins';
        $this->_findPlugins();
    }

    function getAllParameters() {
        return $this->getCoreCommandLineArgs();
    }

    public static function getMagento() {
        static $_magento;
        if (!$_magento) {
            // Did we get a directory from an environment variable?
            $wizMagentoRoot = array_key_exists('WIZ_MAGE_ROOT', $_ENV) ? $_ENV['WIZ_MAGE_ROOT'] : getcwd();

            // Run through all of the options until either we find something, or we've run out of places to look.
            do {
                $magePhpPath = $wizMagentoRoot . WIZ_DS . 'app' . WIZ_DS . 'Mage.php';
                if ($magePhpIsNotFound = !is_readable($magePhpPath)) {
                    $wizMagentoRoot = substr($wizMagentoRoot, 0, strrpos($wizMagentoRoot, WIZ_DS));
                }
            } while ($magePhpIsNotFound && strlen($wizMagentoRoot));

            // No dice. :-(
            if ($magePhpIsNotFound)
                die ('Please specify a Magento root directory by setting WIZ_MAGE_ROOT.'.PHP_EOL);

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

            $_magento = Mage::app('admin');
        }
        return $_magento;
    }

    public function versionAction() {
        echo 'Version: '.Wiz::WIZ_VERSION;
        return TRUE;
    }

    private function _findPlugins() {

        $plugins = array();
        $pluginFiles = new DirectoryIterator($this->pluginDirectory);
        
        foreach ($pluginFiles as $file) {
            if ($file->isFile()) {
                // var_dump($file->getPathname());
                include($file->getPathname());
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
        return TRUE;
    }

    /**
     * List all available actions that Wiz can perform.
     *
     * @return void
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function listActions() {
        echo 'Available commands: '.PHP_EOL;
        foreach ($this->_availableCommands as $commandName => $commandArray) {
            echo $commandName.PHP_EOL;
        }
        return TRUE;
    }

    function _initWiz() {
        // Look for a configuration in .wizrc
        if (file_exists('~/.wizrc')) {
            $configurationOptions = file_get_contents('~/.wizrc');
        }
    }

    public function run() {
        $argv = $_SERVER['argv'];
        // var_dump($argv);
        array_shift($argv);
        $command = array_shift($argv);
        if (array_key_exists($command, $this->_availableCommands)) {
            if ($this->_availableCommands[$command]['class'] == __CLASS__) {
                $pluginInstance = $this;
            }
            else {
                $pluginInstance = new $this->_availableCommands[$command]['class']();
            }
            if (!$pluginInstance->{$this->_availableCommands[$command]['method']}($argv)) {
                echo 'An error occured while processing the command: '.$command.PHP_EOL;
            }
        }
        elseif ($command == '') {
            echo $this->getHelp();
        }
        else {
            echo 'Unable to find that command: '.$command.PHP_EOL;
        }
    }

    public function getHelp() {
        $helpText = 'Wiz version '.Wiz::WIZ_VERSION.PHP_EOL;
        $helpText .= 'Provides a CLI interface to get information from, script, and help you manage'.PHP_EOL;
        $helpText .= 'your Magento CE installation.'.PHP_EOL;
        $helpText .= PHP_EOL;
        $helpText .= 'Available commands:'.PHP_EOL;
        foreach ($this->_availableCommands as $commandName => $commandArray) {
            $helpText .= '  '.$commandName.PHP_EOL;
        }
        return $helpText.PHP_EOL;
    }

}

// This is probably not the best place for this, but it works for now.
class Wiz_Plugin_Abstract {
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
