<?php
/**
*   Admin functions for the Searcher plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    searcher
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

$display = '';
$pi_title = $_SRCH_CONF['pi_display_name'] . ' ' .
            $LANG32[36] . ' ' . $_SRCH_CONF['pi_version'];

// If user isn't a root user or if the backup feature is disabled, bail.
if (!SEC_inGroup('Root')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the Searcher admin screen.");
    COM_404();
    exit;
}


/**
*   Create the main menu
*
*   @param  string  $explanation    Instruction text
*   @return string  HTML for menu area
*/
function SRCH_adminMenu($explanation = '')
{
    global $_CONF, $LANG_ADMIN, $LANG_SRCH, $_SRCH_CONF,$_IMAGE_TYPE;

    USES_lib_admin();

    $retval = '';

    $token = SEC_createToken();
    $menu_arr = array(
        array('url' => SRCH_ADMIN_URL,
              'text' => $LANG_SRCH['generate_all']),
        array('url' => $_CONF['site_admin_url'],
              'text' => $LANG_ADMIN['admin_home']),
    );
    $retval .= COM_startBlock($_SRCH_CONF['pi_display_name'],
                            COM_getBlockTemplate('_admin_block', 'header'));
    $retval .= ADMIN_createMenu(
            $menu_arr, $explanation,
            $_CONF['layout_url'] . '/images/icons/database.' . $_IMAGE_TYPE
    );

    return $retval;
}


$action = '';
$expected = array(
    'genindex',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
    }
}

$content = '';
$message = '';
switch ($action) {
case 'genindex':
    if (!isset($_POST['pi']) || empty($_POST['pi'])) {
        break;
    }
    foreach ($_POST['pi'] as $pi_name=>$checked) {
        $func = 'plugin_IndexAll_' . $pi_name;
        if (function_exists($func)) {
            $count = $func();
            $message .= "<br />$pi_name: Indexed $count Items";
        }
    }
}

switch ($view) {
default:
    $T = new Template(SRCH_PI_PATH . '/templates');
    $T->set_file('admin', 'admin.thtml');
    $T->set_var(array(
        'pi_url' => SRCH_URL,
        'header' => $_SRCH_CONF['pi_display_name'],
        'version' => $_SRCH_CONF['pi_version'],
        'pi_icon' => plugin_geticon_searcher(),
        'menu' => SRCH_adminMenu(),
    ) );
    foreach ($_PLUGINS as $pi_name) {
        if (function_exists('plugin_IndexAll_' . $pi_name)) {
            $T->set_block('admin', 'plugins', 'pRow');
            $T->set_var('pi_name', $pi_name);
            $T->parse('pRow', 'plugins', true);
        }
    }
    $T->parse('output', 'admin');
    $content .= $T->finish($T->get_var('output'));
 break;
}

$display .= COM_siteHeader('menu', $pi_title);
$display .= $content;
$display .= $message;
$display .= COM_siteFooter();

echo $display;

?>
