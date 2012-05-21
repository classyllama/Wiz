# Usage:
# Put the following in ~/.bash_completion
# or add the line below to your .profile/.bashrc/.bash_profile and enjoy: 
# source $WIZ_HOME/wiz.bash_completion.sh

_wiz() {
    local cur prev opts
    COMPREPLY=()
    cur="${COMP_WORDS[COMP_CWORD]}"
    prev="${COMP_WORDS[COMP_CWORD-1]}"
    if [ $COMP_CWORD -eq 1 ]; then prev='wiz'; fi
    case "${prev}" in
        wiz)
            opts="301-urlskumap 301-urlcatmap 301-htgen 301-xmlsm2csv admin-disable admin-enable admin-list admin-resetpass admin-createadmin admin-timeout admin-resources cache-clear cache-enable cache-disable cache-status config-get config-storeget config-xpath config-asxml cron-jobs cron-listeners cron-run devel-showhints devel-logging devel-allowsymlinks devel-config devel-profiler devel-mergejs devel-mergecss devel-listeners devel-models devel-events indexer-status indexer-reindex indexer-realtime indexer-manual log-status log-clean magento-version magento-script magento-shutdown magento-start mc-dl mc-versions module-list module-enable module-disable module-enableoutput module-disableoutput sql-info sql-cli sql-exec store-list command-list help"
            opts="${opts} --store --website --batch"
            ;;
        magento-script)
            opts="$(ls *.php)"
            ;;
        module-disable | module-enable | module-disableoutput | module-enableoutput)
            opts="$(_wiz_mage_exts)"
            ;;
        admin-disable)
            opts="$(wiz admin-list --batch | grep -E ',Active$' | cut -f 2 -d ',' | tr '\n' ' ')"
            ;;
        admin-enable)
            opts="$(wiz admin-list --batch | grep -E ',Inactive$' | cut -f 2 -d ',' | tr '\n' ' ')"
            ;;
        admin-resetpass)
            opts="$(wiz admin-list --batch | cut -f 2 -d ',' | tr '\n' ' ')"
            ;;
        cache-clear)
            opts="$(wiz cache-status --batch | tail -n +2 | cut -f 2 -d ',' | tr '\n' ' ')"
            ;;
        cache-disable)
            opts="$(wiz cache-status --batch | grep -E ',Enabled$' | cut -f 2 -d ',' | tr '\n' ' ')"
            ;;
        cache-enable)
            opts="$(wiz cache-status --batch | grep -E ',Disabled$' | cut -f 2 -d ',' | tr '\n' ' ')"
            ;;
        devel-showhints | devel-logging | devel-allowsymlinks | devel-config | devel-profiler | devel-mergejs | devel-mergecss)
            opts="totally yes 1 true nah no 0 false"
            ;;
        indexer-reindex)
            opts="$(wiz indexer-status --batch | tail -n +2 | sed -e 's/.*(\(.*\)).*/\1/g' | tr '\n' ' ')"
            ;;
        indexer-realtime)
            opts="$(wiz indexer-status --batch | grep -E ',"Manual Update"$' | sed -e 's/.*(\(.*\)).*/\1/g' | tr '\n' ' ')"
            ;;
        indexer-manual)
            opts="$(wiz indexer-status --batch | grep -E ',"Update on Save"$' | sed -e 's/.*(\(.*\)).*/\1/g' | tr '\n' ' ')"
            ;;
    esac

    case "${prev}" in
        admin-list | module-list | admin-resources | cache-status | config-xpath | cron-jobs | cron-listeners | devel-config | devel-listeners | devel-models | indexer-status | store-list)
            opts="${opts} --batch"
    esac

    COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
    return 0
}

_wiz_mage_exts() {
    echo "$(wiz module-list --batch | tail -n +2 | cut -f 1 -d , | tr '\n' ' ')"
}

complete -F _wiz wiz
