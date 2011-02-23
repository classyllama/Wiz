# Wiz

Wiz is a CLI interface for Magento.  It aims to provide useful functionality for developers and administrators in a powerful and scriptable CLI format.

## How do I use it?

1. Put it somewhere in your path.
2. Point it to a Magento directory by setting the <code>WIZ\_MAGE_ROOT</code> environment variable.
3.  Run the program by calling <code>wiz</code> 

## What can it do?

Wiz currently has the following commands: 

### admin-createadmin

Creates an Administrative user in the backend.  Has been tested in both CE and PE.

### cache-clear

Clears the Magento caches.

### cache-enable &lt;name&gt;

Enables Magento caches.

### cache-disable &lt;name&gt;

Disables a Magento cache.

### cache-status

Lists the status of the caches.

### config-get &lt;nodepath&gt;

Gets a configuration value from the global Magento config.

### config-xpath &lt;xpath query&gt;

Runs an XPath query over Magento's configuration object.  For more information on what XPath can do, go check out this [W3School Article on XPath](http://www.w3schools.com/xpath/xpath_syntax.asp).

### config-asxml [ugly]

Dumps Magento's config as XML.  The default is pretty output, but you can pass the ugly parameter to get no newlines or tabs.

### magento-version

Display's Magento's version.

### sql-info

Show's Magento's SQL configuration.

### sql-cli

Launches a MySQL command line session directly to Magento's database.

### sql-exec &lt;query&gt;

Executes a query against the Magento database.

### command-list

Lists all of the available commands.  This will run by default.

### help <command>

Displays more information about a command.  These are pulled from the Docblocks in the source code.

## License

Wiz is licensed under the [OSL 3.0](http://opensource.org/licenses/osl-3.0.php).