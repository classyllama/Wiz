<?php

/**
 * Here is what plugins need to implement by default to be used within the Wiz construct.
 *
 * @package Wiz
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
abstract class Wiz_Plugin_Abstract {

    /**
     * Returns an array of command line arguments that this plugin wishes to register
     * itself for.
     *
     * @return array
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function respondsToCommands() {
        return array();
    }

    /**
     * Function that is called whenever a command that matches the requests from the
     * respondsToCommands() is recieved and is deliver to the proper plugin.
     *
     * @param option null|array Options that were passed from the CLI.
     * @return array
     * @author Nicholas Vahalik <nick@classyllama.com>
     */
    public function processCommand($options = null) {
        return array();
    }
}