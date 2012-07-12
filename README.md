# Wiz

Wiz is a CLI interface for Magento.  It aims to provide useful functionality for developers and administrators in a powerful and scriptable CLI format.

## How do I use it?

1. Install it (instructions below).
2. Navigate to a Magento directory.
3. Run the program by calling <code>wiz</code>.
4. Optionally, you can set the <code>WIZ\_MAGE\_ROOT</code> environment variable to always point Wiz to a specific location.  If you have multiple sites and want to be able to manipulate multiple sites without having to navigate through directories, you can do this:
<br/><code>alias wiz1="WIZ\_MAGE\_ROOT=/path/to/site1 ~/bin/Wiz/wiz"
alias wiz2="WIZ\_MAGE\_ROOT=/path/to/site2 ~/bin/Wiz/wiz"</code><br/>
Now you can run <code>wiz1</code> from any location and it will run inside the context of the site1.

### Setup instructions (for the less technical)

1. Download Wiz and uncompress it somewhere (probably your Downloads folder)
2. Open up a shell, type this in: <code>mkdir -p ~/bin/Wiz; open ~/bin/Wiz</code>
3. Copy all of the files from the folder that was uncompressed into ~/bin/Wiz.  It should be the readme, the app directory, wiz and wiz.php.
4. Using a text editor, add the following to the .bash_profile in your home directory:
<br/><code>alias wiz="~/bin/Wiz/wiz"</code>
5. If you are using Zend Server, you will also need to add the path to PHP 5.2 (since Wiz requires PHP 5.2 at the moment).  On OSX, you would add this to your .bash_profile:
<br/><code>export WIZ\_PHP\_PATH="/usr/local/zend/bin/php"</code>

## Bash Completion

To enable bash shell command/TAB completion, put/append the following in `~/.bash_completion` file:

    source $WIZ_HOME/wiz.bash_completion.sh

(replace `$WIZ_HOME` above with the folder location where you extracted Wiz)

Now you can type: `wiz <TAB>` to get a list of commands.  Futher

## Magento Scope Code

By default, Wiz runs inside of the <code>admin</code> scope. This is great for most operations.  However, you may require running Wiz inside of a particular scope.

You can specify the scope Wiz initializes Magento with by specifying the following options:

<code>\--store [id|code]</code>

<code>\--website [id|code]</code>

By leaving off the id or scope, Wiz will use the default store or website (passing in a blank code).

## Output Mode

Wiz now supports the ability to change the table output for batch handling:

<code>\--batch [csv|pipe|tab]</code>

If passed without a parameter, csv is the default.  Examples:

<code>$ wiz devel-config \--batch<br/>
Path,Value<br/>
dev/debug/profiler,No<br/>
dev/js/merge\_files,No<br/>
.../code>

<code>$ wiz devel-config \--batch pipe<br/>
Path|Value<br/>
dev/debug/profiler|No<br/>
dev/js/merge_files|No<br/>
...</code>

<code>$ wiz devel-config \--batch tab<br/>
Path	Value<br/>
dev/debug/profiler	No<br/>
dev/js/merge_files	No<br/>
...</code>

You get the idea.  Internally, it uses [fgetcsv()](http://php.net/fputcsv), so it should treat your data well.

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

### admin-createadmin  &lt;username&gt; &lt;firstname> &lt;lastname> &lt;email> &lt;password>

Creates an Administrative user in the backend.  Has been tested in both CE, PE, and EE.  If you don't pass the argument, you will be promtped for them.

### admin-disable &lt;username>

Disables an administrative user.

### admin-enable &lt;username>

Enables an administrative user.

### admin-list

Lists the name, e-mail address, and status of every admin in the system.

### admin-resetpass

Resets the password of an admin user.

### admin-timeout <time>

Set the cookie lifetime for admin sessions. 

### cache-clear [all|invalidated|system|js|css|jscss|images]

Clears the Magento caches.

Optional parameters:

* _all_ - Clears Magento's system cache, general caches, js&css, and images caches.
* _invalidated_ - Refreshes all invalidated caches.
* _system_ - Clears Magento's system cache
* _js_, _css_, _jscss_ - Clears the JavaScript & CSS cache.
* _images_ - Clears the images cache

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

### config-asxml [\--ugly]

Dumps Magento's config as XML.  The default is pretty output, but you can pass the ugly parameter to get no newlines or tabs.

### cron-jobs

Lists the jobs that are defined to run when the default cron job is execute.  An interesting point here: the jobs that are defined and listed here do not actually run when the cron event gets dispatched from cron.php.  At least… not directly.  Those jobs are actually managed and executed by the default cron listener (which is provided by Mage_Cron).

### cron-listeners

Lists the event listners that fire when the cron executes.

### cron-run

Runs the cron by firing off the default cron event.

### devel-showhints [true|false|yes|no|1|0|totally|nah]

Without any options, this command simply shows you the global status of template hints and template hint blocks.  By passing it an option, you can either disable or enable template hints globally.  Note that if a site has overridden this value via the system config in the dashboard, it will not have any effect for that site.

### devel-logging [true|false|yes|no|1|0|totally|nah]

Enable, disable, or view the status of Magento's logging.

### devel-allowsymlinks [true|false|yes|no|1|0|totally|nah]

Added in Magento 1.5.1.0, you can now specify the ability to use symlinks for templates.  With this command, you can enable, disable, or view the status of it.

### devel-config

Shows all of the devel configuration path statuses.  Useful for just seeing what is enabled.

### devel-profiler [true|false|yes|no|1|0|totally|nah]

Enable, disable, or view the status of Magento's profiler.

### devel-mergejs [true|false|yes|no|1|0|totally|nah]

Enable, disable, or view the status of JS Merging.

### devel-mergecss [true|false|yes|no|1|0|totally|nah]

Enable, disable, or view the status of CSS Merging.

### devel-listeners

Returns a list of all of the registered listeners.  This will give you a list of observer events and what functions (along with their modules) that will respond to those events.

### devel-models

Outputs a list of models that registered with Magento's runtime.  For regular extension model definitions (not rewrites), it will show you the model prefix (e.g. "catalog/\*") and the module prefix that will handle it (e.g. "Mage\_Catalog\_Model_*").

For rewrites, it will show you the overridden model name and the module class that will be used instead.

### indexer-status

Shows the current status of all indexes.

### indexer-reindex &lt;index&gt;

Reindexes an index.

### indexer-realtime &lt;index&gt;

Sets the index to update on save.

### indexer-manual &lt;index&gt;

Set the index to reindex manually.

### log-status

Displays the status of the logging tables.

### log-clean

Cleans Magento's logging tables.

### magento-version

Display's Magento's version.

### magento-script <filename>

Runs a PHP Script after bootstrapping Magento.  Useful for testing code or writing scripts that need to run inside of Magento without having to write a full-fledged module or a sandbox.

### magento-shutdown

Shuts down Magento by creating the maintenance flag file.

### magento-startup

Allows Magento run by removing the maintenance flag file.

### mc-dl

Downloads a Magento connect package.  Yes, I know you can use ./mage but this also works.

### mc-versions

Lists the available versions of a module that you can download from Magento Connect.

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

### module-enable &lt;module&gt; [&lt;module2&gt;, ..., &lt;modulen&gt;]

Enables one or more modules by modifying their config file's active flag.

### module-disable &lt;module&gt; [&lt;module2&gt;, ..., &lt;modulen&gt;]

Disables one or more modules by modifying their config file's active flag.

### module-enableoutput &lt;module&gt; [&lt;module2&gt;, ..., &lt;modulen&gt;]

Enables output for one or more modules.

### module-disableoutput &lt;module&gt; [&lt;module2&gt;, ..., &lt;modulen&gt;]

Disables output for one or more modules.

### sql-info

Shows Magento's SQL configuration.

### sql-cli

Launches a MySQL command line session directly to Magento's database.

### sql-exec &lt;query&gt;

Executes a query against the Magento database.

### store-list

Shows a list of stores, groups, and websites.  Like a boss.

### command-list

Lists all of the available commands.  This will run by default.

### help <command>

Displays more information about a command.  These are pulled from the Docblocks in the source code.

## License

Wiz is licensed under the [OSL 3.0](http://opensource.org/licenses/osl-3.0.php).