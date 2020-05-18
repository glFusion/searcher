<?php
/**
 * Searcher Plugin for glFusion CMS - reindex site content.
 *
 * @author      Mark R. Evans <mark@glfusion.org>
 * @copyright   Copyright (c) 2017 Mark R. Evans <mark AT glFusion DOT org>
 * @package     searcher
 * @version     v1.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once $_CONF['path'].'plugins/searcher/include/reindex.ajax.php';

/**
 * Create the reindexing options page.
 *
 * @return  string  HTML for the options page
 */
function SRCHER_reindex()
{
    global $_CONF, $_SRCH_CONF, $_PLUGINS, $LANG01, $LANG_ADMIN, $LANG_SRCH, $LANG_SRCH_ADM, $_IMAGE_TYPE;

    $retval = '';

    $T = new \Template(SRCH_PI_PATH . '/templates');
    $T->set_file('page','reindex.thtml');

    $retval .= Searcher\Menu::Admin('reindex');

    $T->set_var('lang_title',$LANG_SRCH_ADM['reindex_title']);

    $T->set_var('lang_conversion_instructions', $LANG_SRCH_ADM['index_instructions']);

    $T->set_block('page', 'contenttypes', 'ct');
    $T->set_var('content_type','article');
    $T->parse('ct', 'contenttypes',true);
    foreach ($_PLUGINS as $pi_name) {
        if (function_exists('plugin_getiteminfo_' . $pi_name)) {
            $T->set_var('content_type',$pi_name);
            $T->parse('ct', 'contenttypes',true);
        }
    }

    $T->set_var('security_token',SEC_createToken());
    $T->set_var('security_token_name',CSRF_TOKEN);
    $T->set_var(array(
        'form_action'       => $_CONF['site_admin_url'].'/plugins/searcher/reindex.php',
        'lang_index'        => $LANG_SRCH_ADM['reindex_button'],
        'lang_cancel'       => $LANG_ADMIN['cancel'],
        'lang_ok'           => $LANG01['ok'],
        'lang_indexing'     => $LANG_SRCH_ADM['indexing'],
        'lang_success'      => $LANG_SRCH_ADM['success'],
        'lang_ajax_status'  => $LANG_SRCH_ADM['index_status'],
        'lang_retrieve_content_types' => $LANG_SRCH_ADM['retrieve_content_types'],
        'lang_error_header' => $LANG_SRCH_ADM['error_heading'],
        'lang_no_errors'    => $LANG_SRCH_ADM['no_errors'],
        'lang_error_getcontenttypes' => $LANG_SRCH_ADM['error_getcontenttypes'],
        'lang_current_progress' => $LANG_SRCH_ADM['current_progress'],
        'lang_overall_progress' => $LANG_SRCH_ADM['overall_progress'],
        'lang_remove_content_1' => $LANG_SRCH_ADM['remove_content_1'],
        'lang_remove_content_2' => $LANG_SRCH_ADM['remove_content_2'],
        'lang_content_type' => $LANG_SRCH_ADM['content_type'],
        'lang_remove_fail'  => $LANG_SRCH_ADM['remove_fail'],
        'lang_retrieve_content_list' => $LANG_SRCH_ADM['retrieve_content_list'],

    ));

    $T->parse('output', 'page');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}

// main driver
$action = '';
$expected = array('reindex','getcontenttypes','getcontentlist','index','removeoldcontent','complete','contentcomplete');
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
    } elseif (isset($_GET[$provided])) {
	    $action = $provided;
    }
}

if ( isset($_POST['cancelbutton'])) COM_refresh($_CONF['site_admin_url'].'/plugins/searcher/index.php');

switch ($action) {
    case 'reindex':
        $pagetitle = $LANG_SRCH['reindex_title'];
        $page .= SRCHER_reindex();
        break;
    case 'getcontenttypes' :
        // return json encoded list of content types
        SRCH_getContentTypesAjax();
        break;
    case 'removeoldcontent' :
        SRCH_removeOldContentAjax();
        break;
    case 'getcontentlist' :
        // return list of all content type ids
        SRCH_getContentListAjax();
        break;
    case 'index' :
        // index a content item via ajax
        SRCH_indexContentItemAjax();
        break;
    case 'contentcomplete' :
        SRCH_completeContentAjax();
        break;
    case 'complete' :
        SRCH_completeAjax();
        break;
    default :
        $page = SRCHER_reindex();
        break;
}

$display  = COM_siteHeader('menu', $LANG_SRCH_ADM['searcher_admin']);
$display .= $page;
$display .= COM_siteFooter();
echo $display;
?>
