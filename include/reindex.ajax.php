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

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own!');
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

function SRCH_removeOldContentAjax()
{
    global $_PLUGINS;

    if ( !COM_isAjax()) die();

    if ( !isset($_POST['type'])) die();

    $type = COM_applyFilter($_POST['type']);
    if ( empty($type) || $type == "" ) die();

    $contentList = array();
    $retval = array();

    Searcher\Indexer::Removeall($type);

    $retval['errorCode'] = 0;
    $retval['statusMessage'] = 'Old Entries Purged';

    $return["js"] = json_encode($retval);

    echo json_encode($return);
    exit;
}

function SRCH_getContentListAjax()
{
    global $_PLUGINS;

    if ( !COM_isAjax()) die();

    if ( !isset($_POST['type'])) die();

    $type = COM_applyFilter($_POST['type']);

    $contentList = array();
    $retval = array();

    $rc = PLG_getItemInfo($type,'*','id,search_index');
    foreach ( $rc AS $id ) {
        $contentList[] = $id;
    }

    Searcher\Indexer::Removeall($type);

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


    $type = COM_applyFilter($_POST['type']);
    $id   = COM_applyFilter($_POST['id']);

    $contentList = array();
    $retval = array();

    $contentInfo = PLG_getItemInfo($type,$id,'id,date,title,searchidx,author,author_name,hits,perms,search_index,reindex,status');

    if ( is_array($contentInfo) && count($contentInfo) > 0 &&
            (!isset($contentInfo['status']) || $contentInfo['status'] == 1) ) {
        $props = array(
            'item_id' => $id,
            'type'  => $type,
            'title' => $contentInfo['title'],
            'content' => $contentInfo['searchidx'],
            'date' => $contentInfo['date'],
            'author' => $contentInfo['author'],
            'author_name' => $contentInfo['author_name'],
            'perms' => array(
                'owner_id' => $contentInfo['perms']['owner_id'],
                'group_id' => $contentInfo['perms']['group_id'],
                'perm_owner' => $contentInfo['perms']['perm_owner'],
                'perm_group' => $contentInfo['perms']['perm_group'],
                'perm_members' => $contentInfo['perms']['perm_members'],
                'perm_anon' => $contentInfo['perms']['perm_anon'],
            ),
        );

        Searcher\Indexer::IndexDoc($props, true);   // call indexer using queue

        if (function_exists('plugin_commentsupport_'.$type ) || $type == 'article' ) {
            if ( $type != 'article' ) {
                $func = 'plugin_commentsupport_'.$type;
                $rc = $func();
            } else {
                $rc = true;
            }
            if ( $rc == true || $type == 'article') {
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

function SRCH_completeContentAjax()
{
    global $_PLUGINS;

    if ( !COM_isAjax()) die();

    Searcher\Indexer::FlushQueue();     // write any pending DB updates

    // $_POST['type'] will hold the content type that was just completed.
    $contentType = isset($_POST['type']) ? COM_applyFilter($_POST['type']) : 'unknown';

    $retval['errorCode'] = 0;
    $retval['statusMessage'] = 'Reindexing ' . $contentType . 'Successful';
    $return["js"] = json_encode($retval);

    echo json_encode($return);
    exit;
}

function SRCH_completeAjax()
{
    global $_PLUGINS;

    if ( !COM_isAjax()) die();

    $retval['errorCode'] = 0;
    $retval['statusMessage'] = 'Reindexing Successful';
    $return["js"] = json_encode($retval);

    echo json_encode($return);
    exit;
}
?>
