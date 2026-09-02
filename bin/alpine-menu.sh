#!/bin/sh
# Interactive admin menu mirroring resources/views/invoice/info/wsl_to_alpine.php
#
# Runs directly at the Alpine command prompt (once you're already SSH'd in --
# steps 1-3 and 22 of the runbook are the Windows/WSL side of getting here,
# so they're not menu items). POSIX /bin/sh only (Alpine's default shell is
# BusyBox ash, not bash -- nothing here relies on bashisms), so it runs with
# no extra packages.
#
# Keep this in sync with wsl_to_alpine.php by hand -- it's a separate file
# on purpose (that one is rendered inside the app as a help page; this one
# has to run as a shell script), but the step numbers below match its
# numbering so the two are easy to cross-reference.
#
# Usage: ./bin/alpine-menu.sh          (or: sh bin/alpine-menu.sh)

APP_ROOT="/var/www/invoice"

confirm() {
    # $1 = prompt text. Returns 0 (yes) or 1 (no/anything else).
    printf '%s [y/N]: ' "$1"
    read -r reply
    case "$reply" in
        y|Y|yes|YES) return 0 ;;
        *) return 1 ;;
    esac
}

pause() {
    printf '\nPress Enter to return to the menu...'
    read -r _
}

run() {
    # $1 = label to echo before running, rest = the actual command
    echo "\$ $*"
    "$@"
    echo "(exit code: $?)"
}

step_04_cd_app_root() {
    cd "$APP_ROOT" || { echo "Could not cd into $APP_ROOT"; return; }
    pwd
}

step_05_verify_git() { run git --version; }

step_06_upgrade_git() {
    confirm "Run apk update && apk upgrade git?" && run apk update && run apk upgrade git
}

step_07_git_status() { cd "$APP_ROOT" && run git status; }

step_08_git_stash() {
    cd "$APP_ROOT" || return
    confirm "Stash local changes? (restore later with: git stash pop)" && run git stash
}

step_09_git_discard() {
    cd "$APP_ROOT" || return
    echo "This discards ALL local changes in $APP_ROOT permanently."
    confirm "Are you sure you want to run: git checkout -- ." && run git checkout -- .
}

step_10_restore_env_from_stash() {
    cd "$APP_ROOT" || return
    confirm "Restore .env from stash@{0}?" && run git checkout stash@{0} -- .env
}

step_11_git_pull() { cd "$APP_ROOT" && run git pull origin main; }

step_11a_verify_env() {
    cd "$APP_ROOT" || return
    if [ -f .env ]; then
        echo ".env exists."
    else
        echo "MISSING .env -- likely to cause a 500 error. Restore it (menu option 10) if you just pulled/reset."
    fi
}

step_11b_verify_items_php() {
    cd "$APP_ROOT" || return
    if [ -f resources/rbac/items.php ]; then
        echo "resources/rbac/items.php exists."
    else
        echo "MISSING resources/rbac/items.php."
    fi
}

step_11c_verify_rbac_assignments() {
    echo "Enter the MySQL root password when prompted."
    run mysql -u root -p yii3_i -e "SELECT * FROM yii_rbac_assignment;"
}

step_11c_assign_roles() {
    cd "$APP_ROOT" || return
    echo "Only run this if step 11c above showed no rows."
    confirm "Assign admin(1)/observer(2) roles now?" || return
    run php yii user/assignRole admin 1
    run php yii user/assignRole observer 2
}

step_12a_mysql_shell() {
    echo "Opening an interactive mysql shell (root) -- type \\q or exit to leave it."
    mysql -u root -p
}

step_12b_phpmyadmin_reminder() {
    cat <<'EOF'
Reminder (no command to run): make sure the phpMyAdmin endpoint is not
publicly visible, and don't use phpMyAdmin even with IP restrictions/
aliasing. Use option matching step 23b below to check where/if it's
installed.
EOF
}

step_13_git_stash_list() { cd "$APP_ROOT" && run git stash list; }

step_15_git_stash_show() { cd "$APP_ROOT" && run git stash show -p; }

step_16_fix_ownership() {
    confirm "Apply apache:apache ownership + 755/775 permissions under $APP_ROOT?" || return
    run chown -R apache:apache "$APP_ROOT/"
    run chown -R apache:apache "$APP_ROOT/resources/rbac/assignments.php"
    run chown -R apache:apache "$APP_ROOT/resources/rbac/items.php"
    run chmod -R 755 "$APP_ROOT/"
    run chmod -R 775 "$APP_ROOT/resources/"
    run chmod -R 775 "$APP_ROOT/runtime/"
    run chmod -R 775 "$APP_ROOT/public/assets/"
}

step_18_check_smtp_port() {
    if ! command -v telnet >/dev/null 2>&1; then
        confirm "telnet not found -- install it (apk add busybox-extras)?" && run apk add busybox-extras
    fi
    echo "Checking smtp.gmail.com:465 -- 'Connection closed by foreign host' is NORMAL here"
    echo "(Gmail expects an SSL handshake, not raw telnet). Ctrl+] then 'quit' to exit telnet if it hangs."
    run telnet smtp.gmail.com 465
}

step_19_clear_logs() {
    cd "$APP_ROOT" || return
    confirm "Delete runtime/logs/*.log? (Yii rebuilds them automatically)" && run rm -f runtime/logs/*.log
}

step_21_show_mailer_settings() {
    cd "$APP_ROOT" || return
    run grep -A 30 "yiisoft/mailer-symfony" config/common/params.php
}

step_23_reload_apache() {
    echo "If you need to edit ssl.conf first: nano /etc/apache2/conf.d/ssl.conf"
    confirm "Test and restart Apache now (httpd -t && rc-service apache2 restart)?" || return
    run httpd -t && run rc-service apache2 restart
}

step_23b_find_phpmyadmin() {
    echo "Searching / for a phpMyAdmin install (may take a moment)..."
    run find / -name "index.php" -path "*/phpmyadmin/*" 2>/dev/null
}

step_24_mariadb_status() { run rc-service mariadb status; }

step_25_mariadb_restart() {
    confirm "Restart mariadb?" && run rc-service mariadb restart
}

step_26_clear_route_cache() {
    confirm "Clear $APP_ROOT/runtime/cache/*? (Yii3 rebuilds it on the next request)" || return
    run rm -rf "$APP_ROOT/runtime/cache/"*
}

step_27_backup_database() {
    echo "Enter the MySQL root password when prompted."
    ts=$(date +%Y%m%d_%H%M%S)
    out="/tmp/invoice_backup_${ts}.sql.gz"
    echo "Backing up to $out"
    mysqldump -u root -p --single-transaction yii3_i | gzip > "$out"
    echo "Done: $out"
    echo "From your local machine: scp root@yii3i.online:$out /mnt/c/wamp64/www/invoice/backups/"
}

step_28_update_node() {
    cd "$APP_ROOT" || return
    run apk update
    run apk info nodejs
    confirm "Run apk upgrade nodejs npm now?" && run apk upgrade nodejs npm
    run node -v
    echo "If a specific version isn't offered by the enabled repos: apk search nodejs"
    echo "If Apache can't find node after an update, point NODE_BINARY in .env at \`which node\`."
}

show_menu() {
    cat <<'EOF'

==================== Alpine deployment menu ====================
(mirrors resources/views/invoice/info/wsl_to_alpine.php)

 4) cd into app root and confirm it
 5) Verify git is installed
 6) Upgrade git
 7) git status
 8) git stash (save local changes)
 9) git checkout -- .  (DISCARD local changes)
10) Restore .env from stash@{0}
11) git pull origin main
11a) Verify .env exists
11b) Verify resources/rbac/items.php exists
11c) Verify RBAC role assignments (mysql)
11d) Assign admin/observer roles if missing
12a) Open an interactive mysql shell
12b) phpMyAdmin exposure reminder
13) git stash list
15) git stash show -p
16) Fix ownership & permissions (chown/chmod)
18) Install telnet & check smtp port 465
19) Clear runtime/logs/*.log
21) Show mailer settings (grep params.php)
23) Test config & restart Apache
23b) Find any phpMyAdmin install on disk
24) Check mariadb status
25) Restart mariadb
26) Clear the Yii3 route cache
27) Backup the database (mysqldump + gzip)
28) Update Node via apk
 q) Quit
===================================================================
EOF
}

while true; do
    show_menu
    printf 'Choice: '
    read -r choice
    case "$choice" in
        4) step_04_cd_app_root ;;
        5) step_05_verify_git ;;
        6) step_06_upgrade_git ;;
        7) step_07_git_status ;;
        8) step_08_git_stash ;;
        9) step_09_git_discard ;;
        10) step_10_restore_env_from_stash ;;
        11) step_11_git_pull ;;
        11a) step_11a_verify_env ;;
        11b) step_11b_verify_items_php ;;
        11c) step_11c_verify_rbac_assignments ;;
        11d) step_11c_assign_roles ;;
        12a) step_12a_mysql_shell ;;
        12b) step_12b_phpmyadmin_reminder ;;
        13) step_13_git_stash_list ;;
        15) step_15_git_stash_show ;;
        16) step_16_fix_ownership ;;
        18) step_18_check_smtp_port ;;
        19) step_19_clear_logs ;;
        21) step_21_show_mailer_settings ;;
        23) step_23_reload_apache ;;
        23b) step_23b_find_phpmyadmin ;;
        24) step_24_mariadb_status ;;
        25) step_25_mariadb_restart ;;
        26) step_26_clear_route_cache ;;
        27) step_27_backup_database ;;
        28) step_28_update_node ;;
        q|Q) echo "Bye."; exit 0 ;;
        *) echo "Unknown choice: $choice" ;;
    esac
    pause
done
