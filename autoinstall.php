<?php
/**
*   Provides automatic installation of the Searcher plugin.
*   There is nothing to do except create the plugin record
*   since there are no tables or user interfaces.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner <lee@leegarner.com>
*   @package    searcher
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** @global string $_DB_dbms */
global $_DB_dbms;

require_once __DIR__ . '/functions.inc';
require_once __DIR__ . '/searcher.php';
require_once __DIR__ . "/sql/{$_DB_dbms}_install.php";

//  Plugin installation options
$INSTALL_plugin[$_SRCH_CONF['pi_name']] = array(
    'installer' => array('type' => 'installer', 
            'version' => '1', 
            'mode' => 'install'),

    'plugin' => array('type' => 'plugin', 
            'name'      => $_SRCH_CONF['pi_name'],
            'ver'       => $_SRCH_CONF['pi_version'], 
            'gl_ver'    => $_SRCH_CONF['gl_version'],
            'url'       => $_SRCH_CONF['pi_url'], 
            'display'   => $_SRCH_CONF['pi_display_name']
    ),
       
    array('type' => 'table', 
            'table'     => $_TABLES['searcher_index'], 
            'sql'       => $_SQL['searcher_index']),

);
    
 
/**
*   Puts the datastructures for this plugin into the glFusion database
*   Note: Corresponding uninstall routine is in functions.inc
*
*   @return boolean     True if successful False otherwise
*/
function plugin_install_searcher()
{
    global $INSTALL_plugin, $_SRCH_CONF;

    COM_errorLog("Attempting to install the {$_SRCH_CONF['pi_name']} plugin", 1);
    $ret = INSTALLER_install($INSTALL_plugin[$_SRCH_CONF['pi_name']]);
    if ($ret > 0) {
        return false;
    } else {
        return true;
    }
}


/**
*   Automatic removal function.
*
*   @return array       Array of items to be removed.
*/
function plugin_autouninstall_searcher()
{
    $out = array (
        'tables'    => array('searcher_index'),
        'groups'    => array(),
        'features'  => array(),
        'php_blocks' => array(),
        'vars'      => array(),
    );
    return $out;
}


/**
*   Loads the configuration records for the Online Config Manager.
*
*   @return boolean     True = proceed, False = an error occured
*/
function plugin_load_configuration_searcher()
{
    require_once dirname(__FILE__) . '/install_defaults.php';
    return plugin_initconfig_searcher();
}

?>
