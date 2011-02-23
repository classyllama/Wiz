<?php

/**
 * Here is what plugins need to implement by default to be used within the Wiz construct.
 *
 * @package Wiz
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
class Wiz_Plugin_Version extends Wiz_Plugin_Abstract {

    /**
     * Returns an array of command line arguments that this plugin wishes to register
     * itself for.
     *
     * @return array
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function respondsToCommands() {
        return array(
            'v', 'version'
        );
    }

    /**
     * Function that is called whenever a command that matches the requests from the
     * respondsToCommands() is recieved and is deliver to the proper plugin.
     *
     * @param option null|object Options that were passed from the CLI.
     * @return array
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function processCommand($options = null) {
        if ($option->isBatchMode()) {
            echo Wiz::getVersion();
        } else {
            echo 'Wiz version: '.Wiz::getVersion();
        }
    }
}