# Usage:
# Put the following in ~/.bash_completion
# source $WIZ_HOME/wiz.bash_completion.sh

_wiz() {
	local cur prev opts
	COMPREPLY=()
	cur="${COMP_WORDS[COMP_CWORD]}"
	prev="${COMP_WORDS[COMP_CWORD-1]}"
	opts='
		devel-showhints
		devel-logging
		devel-allowsymlinks
		devel-config
		devel-profiler
		devel-mergejs
		devel-mergecss
		devel-listeners
		devel-models
		devel-events
		log-status
		log-clean
		cache-clear
		cache-enable
		cache-disable
		cache-status
		store-list
		module-list
		module-enable
		module-disable
		module-enableoutput
		module-disableoutput
		module-create
		config-get
		config-storeget
		config-xpath
		config-asxml
		config-defaultset
		indexer-status
		indexer-reindex
		indexer-realtime
		indexer-manual
		sql-info
		sql-cli
		sql-exec
		admin-createadmin
		magento-version
		magento-script
		301-urlskumap
		301-urlcatmap
		301-htgen
		301-xmlsm2csv
		command-list
		help
		update'
	COMPREPLY=( $(compgen -W "$opts" -- "$cur") )
	return 0
}

complete -F _wiz wiz
