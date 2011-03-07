# Wiz

Wiz is a CLI interface for Magento.  It aims to provide useful functionality for developers and administrators in a powerful and scriptable CLI format.

## How do I use it?

1. Put it somewhere in your path.
2. Point it to a Magento directory by setting the <code>WIZ\_MAGE_ROOT</code> environment variable.
3.  Run the program by calling <code>wiz</code> 

## What can it do?

Functionality is being added to Wiz as time allows.  Currently, Wiz has the following commands.  Please understand that not all of these commands have been tested on every version.  Commands that have not been tested or are in beta are noted as such.

### 301-urlskumap

Dumps a CSV of all SKUs in the catalog in the first column and the link to the product in the second column.  Useful for generating redirects or just plain exporting all product URLs.

### 301-urlcatmap (BETA)

Dumps a CSV of all Categories in the catalog in the first column and a link to the product in the second column.

### 301-htgen (BETA)

Generates .htaccess compatible 301 redirects.  Takes a path to a CSV file as parameter.  CSV file will have the old URL and the SKU as column.  The command will cycle through each SKU and create redirects from the old URL to the new URL using the SKU.

### 301-xmlsm2csv (*UNSTABLE*)

Takes an XML Sitemap and converts it to CSV.  _This functionality is not quite finished yet._

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

### devel-showhints [true|false|yes|no|1|0|totally|nah]

Without any options, this command simply shows you the global status of template hints and template hint blocks.  By passing it an option, you can either disable or enable template hints globally.  Note that if a site has overridden this value via the system config in the dashboard, it will not have any effect for that site.

### magento-version

Display's Magento's version.

### magento-script <filename>

Runs a PHP Script after bootstrapping Magento.  Useful for testing code or writing scripts that need to run inside of Magento without having to write a full-fledged module or a sandbox.

### module-list

Displays a list of all modules on the system.  Shows module name, version, active, output, and code pool.

<pre>+------------------------------+------------+--------+----------+-----------+
| Module Name                  | Version    | Active | Output   | Code Pool |
+------------------------------+------------+--------+----------+-----------+
| Mage_Core                    | 0.8.26     | Active | Enabled  | core      |
| Mage_Eav                     | 0.7.15     | Active | Enabled  | core      |
| Mage_Page                    | 0.7.0      | Active | Enabled  | core      |
| Mage_Install                 | 0.7.0      | Active | Enabled  | core      |
| ...                          | ...        | ...    | ...      | ...       |
+------------------------------+------------+--------+----------+-----------+
</pre>

### module-enable <module> [<module2>, ..., <modulen>]

Enables one or more modules by modifying their config file's active flag.

### module-disable <module> [<module2>, ..., <modulen>]

Disables one or more modules by modifying their config file's active flag.

### module-enableoutput <module> [<module2>, ..., <modulen>]

Enables output for one or more modules.

### module-disableoutput <module> [<module2>, ..., <modulen>]

Disables output for one or more modules.

### sql-info

Shows Magento's SQL configuration.

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