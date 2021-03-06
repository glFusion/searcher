<?php
/**
*   Upgrade routines for the Searcher plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    searcher
*   @version    0.0.4
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// Required to get the config values
global $_CONF, $_SRCH_CONF, $_DB_dbms;

/** Include the default configuration values */
require_once __DIR__ . '/install_defaults.php';

/** Include the table creation strings */
require_once __DIR__ . "/sql/{$_DB_dbms}_install.php";

/**
*   Perform the upgrade starting at the current version.
*
*   @return boolean     True on success, False on failure
*/
function SRCH_do_upgrade()
{
    global $_SRCH_DEFAULTS, $_SRCH_CONF, $_PLUGIN_INFO;

    if (isset($_PLUGIN_INFO[$_SRCH_CONF['pi_name']])) {
        if (is_array($_PLUGIN_INFO[$_SRCH_CONF['pi_name']])) {
            // glFusion > 1.6.5
            $current_ver = $_PLUGIN_INFO[$_SRCH_CONF['pi_name']]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[$_SRCH_CONF['pi_name']];
        }
    } else {
        return false;
    }
    $installed_ver = plugin_chkVersion_searcher();

    // Get the config object
    $c = config::get_instance();

    if (!COM_checkVersion($current_ver, '0.0.2')) {
        // upgrade from 0.0.1 to 0.0.2
        $current_ver = '0.0.2';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('max_occurrences', $_SRCH_DEFAULTS['max_occurrences'],
                'text', 0, 0, 0, 40, true, $_SRCH_CONF['pi_name']);
        if (!SRCH_do_upgrade_sql($current_ver)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.3')) {
        // upgrade from 0.0.2 to 0.0.3
        $current_ver = '0.0.3';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('show_author', $_SRCH_DEFAULTS['show_author'],
                'select', 0, 0, 11, 50, true, $_SRCH_CONF['pi_name']);
        $c->add('stemmer', $_SRCH_DEFAULTS['stemmer'],
                'select', 0, 0, 0, 60, true, $_SRCH_CONF['pi_name']);
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.4')) {
        // upgrade from 0.0.3 to 0.0.4
        $current_ver = '0.0.4';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.5')) {
        // upgrade from 0.0.4 to 0.0.5
        $current_ver = '0.0.5';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.6')) {
        // upgrade from 0.0.5 to 0.0.6
        $current_ver = '0.0.6';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.7')) {
        // upgrade from 0.0.6 to 0.0.7
        $current_ver = '0.0.7';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('max_word_phrase', $_SRCH_DEFAULTS['max_word_phrase'],
                'text', 0, 0, NULL, 15, true, $_SRCH_CONF['pi_name']);
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.8')) {
        // upgrade from 0.0.7 to 0.0.8
        $current_ver = '0.0.8';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('replace_stock_search', $_SRCH_DEFAULTS['replace_stock_search'],
                'select', 0, 0, 1, 70, true, $_SRCH_CONF['pi_name']);
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.9')) {
        // upgrade from 0.0.8 to 0.0.9
        $current_ver = '0.0.9';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    // Final version setting in case there was no upgrade process for
    // this version
    // Final extra check to catch code-only patch versions
    if (!COM_checkVersion($current_ver, $installed_ver)) {
        if (!SRCH_do_set_version($installed_ver)) return false;
    }
    return true;
}


/**
*   Actually perform any sql updates.
*
*   @param  string  $version    Version being upgraded TO
*   @return boolean         True on success, False on failure
*/
function SRCH_do_upgrade_sql($version)
{
    global $_TABLES, $_SRCH_CONF, $_UPGRADE_SQL;

    // If no sql statements passed in, return success
    if (!isset($_UPGRADE_SQL[$version]) ||
            !is_array($_UPGRADE_SQL[$version]))
        return true;

    // Execute SQL now to perform the upgrade
    COM_errorLOG("--Updating Searcher to version $version");
    foreach ($_UPGRADE_SQL[$version] as $q) {
        COM_errorLOG("Searcher Plugin $version update: Executing SQL => $q");
        DB_query($q, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during Searcher plugin update: $q",1);
            return false;
        }
    }
    return true;
}


/**
*   Update the plugin version number in the database.
*   Called at each version upgrade to keep up to date with
*   successful upgrades.
*
*   @param  string  $ver    New version to set
*   @return boolean         True on success, False on failure
*/
function SRCH_do_set_version($ver)
{
    global $_TABLES, $_SRCH_CONF;

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '{$_SRCH_CONF['pi_version']}',
            pi_gl_version = '{$_SRCH_CONF['gl_version']}',
            pi_homepage = '{$_SRCH_CONF['pi_url']}'
        WHERE pi_name = '{$_SRCH_CONF['pi_name']}'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_SRCH_CONF['pi_display_name']} Plugin version",1);
        return false;
    } else {
        return true;
    }
}

?>
