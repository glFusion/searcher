<?php
/**
 * Upgrade routines for the Searcher plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v0.0.4
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// Required to get the config values
global $_CONF, $_SRCH_CONF, $_DB_dbms;

/** Include the table creation strings */
require_once __DIR__ . "/sql/mysql_install.php";

/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True if this is a development update
 * @return  boolean True on success, False on failure
 */
function SRCH_do_upgrade($dvlp=false)
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

    if (!COM_checkVersion($current_ver, '0.0.2')) {
        // upgrade from 0.0.1 to 0.0.2
        $current_ver = '0.0.2';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.3')) {
        // upgrade from 0.0.2 to 0.0.3
        $current_ver = '0.0.3';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.4')) {
        // upgrade from 0.0.3 to 0.0.4
        $current_ver = '0.0.4';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.5')) {
        // upgrade from 0.0.4 to 0.0.5
        $current_ver = '0.0.5';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.6')) {
        // upgrade from 0.0.5 to 0.0.6
        $current_ver = '0.0.6';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.7')) {
        // upgrade from 0.0.6 to 0.0.7
        $current_ver = '0.0.7';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.8')) {
        // upgrade from 0.0.7 to 0.0.8
        $current_ver = '0.0.8';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.9')) {
        // upgrade from 0.0.8 to 0.0.9
        $current_ver = '0.0.9';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.2')) {
        // upgrade to 0.0.9
        $current_ver = '0.1.2';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!SRCH_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!SRCH_do_set_version($current_ver)) return false;
    }

    // Final version setting in case there was no upgrade process for
    // this version
    // Final extra check to catch code-only patch versions
    if (!COM_checkVersion($current_ver, $installed_ver)) {
        if (!SRCH_do_set_version($installed_ver)) return false;
    }

    // Sync the plugin configuration items
    include_once __DIR__ . '/install_defaults.php';
    plugin_updateconfig_searcher();

    // Remove deprecated files
    SRCH_remove_old_files();

    return true;
}


/**
 * Actually perform any sql updates.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   boolean $ignore_errors  True to ignore errors for dvlpupdate
 * @return  boolean         True on success, False on failure
 */
function SRCH_do_upgrade_sql($version, $ignore_errors=false)
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
            if (!$ignore_errors) return false;
        }
    }
    return true;
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
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


/**
 * Remove deprecated files
 * Errors in unlink() and rmdir() are ignored.
 */
function SRCH_remove_old_files()
{
    global $_CONF;

    $dir = __DIR__ . '/classes/stemmer';
    if (is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_file($path)) @unlink($path);
        }
        @rmdir($dir);
    }
}

?>
