<?php
/**
*   Searcher Plugin for glFusion CMS - reindex site content
*
*   @author     Mark R. Evans <mark@glfusion.org>
*   @copyright  Copyright (c) 2017 Mark R. Evans <mark AT glFusion DOT org>
*   @package    searcher
*   @version    0.0.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once 'admin.inc.php';

USES_lib_admin();

function SRCHER_reindex()
{
    global $_CONF, $_SRCH_CONF, $_PLUGINS, $LANG01, $LANG_ADMIN, $LANG_SRCH, $LANG_SRCH_ADM, $_IMAGE_TYPE;

    $retval = '';

    $T = new Template(SRCH_PI_PATH . '/templates');
    $T->set_file('page','reindex.thtml');

    $retval .= SRCH_adminMenu('reindex');

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
    ));

    $T->parse('output', 'page');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}


function SRCH_getContentTypesAjax()
{
    global $_PLUGINS;

    if ( !COM_isAjax()) die();

    $contentTypes = array();
    $retval = array();

    $contentTypes[] = 'article';

    foreach ($_PLUGINS as $pi_name) {
        if (function_exists('plugin_getiteminfo_' . $pi_name)) {
            $contentTypes[] = $pi_name;
        }
    }

    $retval['errorCode'] = 0;
    $retval['contenttypes'] = $contentTypes;

    $retval['statusMessage'] = 'Initialization Successful';

    $return["js"] = json_encode($retval);

    echo json_encode($return);
    exit;
}


function SRCH_getContentListAjax()
{
    global $_PLUGINS;

    if ( !COM_isAjax()) die();

    USES_searcher_class_indexer();

    if ( !isset($_POST['type'])) die();

    $type = COM_applyFilter($_POST['type']);

    $contentList = array();
    $retval = array();

    $rc = PLG_getItemInfo($type,'*','id,search_index');
    foreach ( $rc AS $id ) {
        $contentList[] = $id;
    }

    \Searcher\Indexer::Removeall($type);

    $retval['errorCode'] = 0;
    $retval['contentlist'] = $contentList;
    $retval['statusMessage'] = 'Content List Successful';

    $return["js"] = json_encode($retval);

    echo json_encode($return);
    exit;
}

function SRCH_indexContentItemAjax()
{
    global $_PLUGINS;

    if ( !COM_isAjax()) die();

    if ( !isset($_POST['type'])) die();
    if ( !isset($_POST['id'])) die();

    USES_searcher_class_indexer();

    $type = COM_applyFilter($_POST['type']);
    $id   = COM_applyFilter($_POST['id']);

    $contentList = array();
    $retval = array();

    $contentInfo = PLG_getItemInfo($type,$id,'id,date,title,searchidx,author,hits,perms,search_index,reindex');

    if ( is_array($contentInfo) && count($contentInfo) > 0 ) {
        $props = array(
            'item_id' => $id,
            'type'  => $type,
            'title' => $contentInfo['title'],
            'content' => $contentInfo['searchidx'],
            'perms' => array(
                'owner_id' => $contentInfo['perms']['owner_id'],
                'group_id' => $contentInfo['perms']['group_id'],
                'perm_owner' => $contentInfo['perms']['perm_owner'],
                'perm_group' => $contentInfo['perms']['perm_group'],
                'perm_members' => $contentInfo['perms']['perm_members'],
                'perm_anon' => $contentInfo['perms']['perm_anon'],
            ),
        );

        \Searcher\Indexer::IndexDoc($props);

        if (functions_exists('plugin_commentsupport_'.$type ) ) {
            $func = 'plugin_commentsupport_'.$type;
            $rc = $func();
            if ( $rc == true ) {
                plugin_IndexAll_comments($type, $id, $props['perms']);
            }
        }

        $retval['errorCode'] = 0;
        $retval['statusMessage'] = 'Content Item Index Successful';
    } else {
        $retval['errorCode'] = -1;
        $retval['statusMessage'] = 'Error indexing content';
    }

    $return["js"] = json_encode($retval);

    echo json_encode($return);
    exit;
}

// main driver
$action = '';
$expected = array('reindex','getcontenttypes','getcontentlist','index');
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
    case 'getcontentlist' :
        // return list of all content type ids
        SRCH_getContentListAjax();
        break;
    case 'index' :
        // index a content item via ajax
        SRCH_indexContentItemAjax();
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