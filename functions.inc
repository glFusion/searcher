<?php
/**
*   glFusion API functions for the Searcher plugin.
*   This plugin's job is simply to load javascript and css files that are
*   required by other plugins.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012-2017 Lee Garner <lee@leegarner.com>
*   @package    searcher
*   @version    1.0.5
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/


if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

/** Import configuration-handling class */
require_once $_CONF['path_system'] . 'classes/config.class.php';
$lgu_config = config::get_instance();
$_SRCH_CONF = $lgu_config->get_config('searcher');

/** Additional config items and table definitions are added here */
require_once __DIR__ . '/searcher.php';
define('SRCH_PI_PATH', __DIR__);
define('SRCH_URL', $_CONF['site_url'] . '/' . $_SRCH_CONF['pi_name']);
define('SRCH_ADMIN_URL', $_CONF['site_admin_url'] . '/plugins/' . $_SRCH_CONF['pi_name']);

$_SRCH_CONF['_is_uikit'] = $_SYSTEM['framework'] == 'uikit' ? true : false;

/**
*   Include the plugin-specific language file, and the corresponding
*   admin-created custom language file, if any.  Looks for the site
*   language first, then defaults to english.php if nothing else found.
*/
$langpath = SRCH_PI_PATH . '/language';
if (is_file("$langpath/{$_CONF['language']}.php")) {
    $langfile = "{$_CONF['language']}.php";
} else {
    $langfile = 'english.php';
}
require_once "$langpath/$langfile";

if (is_file("$langpath/custom/$langfile")) {
    /** Include any custom language file, if found */
    include_once "$langpath/custom/$langfile";
}

/** Include the user-supplied configuration file, if any */
if (is_file(SRCH_PI_PATH . '/config.php')) {
    include_once SRCH_PI_PATH . '/config.php';
}

/** Upgrade functions */
function USES_searcher_upgrade()
{   require_once SRCH_PI_PATH . '/upgrade.inc.php';   }

/** Common Base class */
function USES_searcher_class_common()
{   require_once SRCH_PI_PATH . '/classes/Common.class.php';    }

/** Common Base class */
function USES_searcher_class_searchform()
{   require_once SRCH_PI_PATH . '/classes/SearchForm.class.php';    }

/** Searcher class */
function USES_searcher_class_searcher()
{   require_once SRCH_PI_PATH . '/classes/Searcher.class.php';    }

/** Indexer class */
function USES_searcher_class_indexer()
{   require_once SRCH_PI_PATH . '/classes/Indexer.class.php';    }

function USES_search_class_stemmer()
{   require_once __DIR__ . '/classes/Stemmer.class.php'; }

/**
*   Get the currently installed version.
*
*   @return string  version number
*/
function plugin_chkVersion_searcher()
{
    global $_SRCH_CONF;
    return $_SRCH_CONF['pi_version'];
}


/**
*   Puts an option for the plugin in the command and control block.
*   Not used in glFusion versions > 1.6.0 since the database backup function
*   has been updated in the main DB Backup option.
*
*   @return   array  Array of (plugin name, admin url, url of plugin icon)
*/
function plugin_cclabel_searcher()
{
    global $LANG_SRCH, $_CONF;

    if (SEC_inGroup('Root')) {
        return array(
            $LANG_SRCH['menu_label'],
            SRCH_ADMIN_URL . '/index.php',
            plugin_geticon_searcher(),
        );
    } else {
        return '';
    }
}


/**
*   Adds the plugin to the Admin menu.
*
*   @return array   Array(pi_name, admin url, # of items) or '' for none.
*/
function plugin_getadminoption_searcher()
{
    global $_CONF, $LANG_SRCH;

    if (SEC_inGroup('Root')) {
        $retval = array(
            $LANG_SRCH['menu_label'],
            SRCH_ADMIN_URL . '/index.php',
            0,
        );
    } else {
        $retval = '';
    }
    return $retval;
}


/**
*   Returns the URL of the plugin's icon.
*
*   @return string      URL of the icon
*/
function plugin_geticon_searcher()
{
    return SRCH_URL . '/images/searcher.png';
}


/**
*   Get the link to a documentation file.
*   Returns the URL to the file, or to the directory if no file requested.
*
*   @see    plugin_getconfigelementhelp_subscription()
*   @param  string  $file       HTML file
*   @param  string  $pi_name    Name of plugin, default "searcher"
*   @param  string  $doclang    Documentation language, default English
*   @return string              URL to document directory or file
*/
function SRCH_getDocURL($file='', $pi_name='searcher', $doclang='english')
{
    global $_CONF, $_SRCH_CONF;

    $doc_path = "{$_CONF['path_html']}$pi_name/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        // Split out any .html extension to insert ".legacy" if needed
        $fparts = pathinfo($file);
        $file = $fparts['filename'];
        if (version_compare(GVERSION, '1.6.0', '<')) {
            $file .= '.legacy';
        }
        $file .= '.html';

        if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = "{$_CONF['site_url']}/$pi_name/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = "{$_CONF['site_url']}/$pi_name/docs/$doclang/";
    }
    return $doc_url;
}


/**
*   Get the URL to the configuration help file.
*
*   @param  string  $option     Config option selected
*   @param  string  $doclang    Document language
*   @return string              URL to document
*/
function plugin_getconfigelementhelp_searcher($option, $doclang='english')
{
    global $_SRCH_CONF;

    $doc_url = SRCH_getDocURL('config.html', $_SRCH_CONF['pi_name'], $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $file .= '#desc_' . $option;
    }

    return array($doc_url . $file, 0);
}


/**
*   Upgrade the plugin to the currently-installed version.
*
*   @return boolean True if successful, False otherwise
*/
function plugin_upgrade_searcher()
{
    require_once __DIR__ . '/upgrade.inc.php';
    return SRCH_do_upgrade();
}


/**
*   Get additional CSS into the site header.
*   Allows for custom CSS to be placed by the site admin.
*
*   @return array   List of paths to CSS files
*/
function plugin_getheadercss_searcher()
{
    global $_CONF, $_SRCH_CONF;

    $styles = array();
    $files = array(
        'style.css',
    );

    // When looking for CSS files, we'll check in this order:
    //  1. layout/plugin/custom
    //  2. layout/plugin
    //  3. plugin/custom
    //  4. plugin (default distribution version)
    $pi_css = SRCH_PI_PATH . '/css/';
    $layout_css = "{$_CONF['path_layout']}plugins/{$_SRCH_CONF['pi_name']}/";
    foreach ($files as $file) {
        if (@file_exists($layout_css . 'custom/' . $file)) {
            $styles[] = $layout_css . 'custom/' . $file;
        } elseif (@file_exists($layout_css . $file)) {
            $styles[] = $layout_css . $file;
        } elseif (@file_exists($pi_css . 'custom/' . $file)) {
            $styles[] = $pi_css . 'custom/' . $file;
        } elseif (@file_exists($pi_css . $file)) {
            $styles[] = $pi_css . $file;
        }
    }
    return $styles;
}


/**
*   Provides the getSearchInfo function for the built-in comment type
*
*   @param  string  $id     Comment ID
*   @return array           Article information for search display
*/
function plugin_getSearchInfo_comment($id)
{
    global $_TABLES, $_CONF;

    static $names = array();

    $retval = array();
    $sql = "SELECT sid, title, uid, comment,
                UNIX_TIMESTAMP(date) as ts
            FROM {$_TABLES['comments']}
            WHERE cid= '" . DB_escapeString($id) . "'";
    $res = DB_query($sql);
    if (DB_numRows($res) == 1) {
        $A = DB_fetchArray($res, false);
        $author = SRCH_getAuthorName($A['uid']);
        $retval = array(
            'title' => $A['title'],
            'content' => $A['comment'],
            'uid' => $A['uid'],
            'author' => $author,
            'date' => $A['ts'],
            'url' => $_CONF['site_url'] . '/article.php?story=' . urlencode($A['sid']),
        );
    }
    return $retval;
}


/**
*   Provides the getSearchInfo function for the built-in article type
*
*   @param  string  $id     Article ID
*   @return array           Article information for search display
*/
function plugin_getSearchInfo_article($id)
{
    global $_TABLES, $_CONF;

    static $names = array();

    $retval = array();
    $sql = "SELECT title, uid, introtext, bodytext, hits,
                UNIX_TIMESTAMP(date) as ts, attribution_author
            FROM {$_TABLES['stories']}
            WHERE sid= '" . DB_escapeString($id) . "'" .
            COM_getPermSQL('AND');
    $res = DB_query($sql);
    if (DB_numRows($res) == 1) {
        $A = DB_fetchArray($res, false);
        $author = empty($A['attribution_author']) ?
                SRCH_getAuthorName($A['uid']) : $A['attribution_author'];
        $retval = array(
            'title' => $A['title'],
            'content' => $A['introtext'] . ' ' . $A['bodytext'],
            'uid' => $A['uid'],
            'author' => $author,
            'date' => $A['ts'],
            'hits' => $A['hits'],
            'url' => $_CONF['site_url'] . '/article.php?story=' . urlencode($id),
        );
    }
    return $retval;
}


/**
*   Provides the getSearchInfo function for staticpages.
*   This should move to the staticpages plugin.
*
*   @param  string  $id     Article ID
*   @return array           Article information for search display
*/
function plugin_getSearchInfo_staticpages($id)
{
    global $_TABLES, $_CONF;

    static $names = array();

    $retval = array();
    $sql = "SELECT sp_title, sp_uid, sp_content, sp_hits,
                UNIX_TIMESTAMP(sp_date) as ts
            FROM {$_TABLES['staticpage']}
            WHERE sp_id = '" . DB_escapeString($id) . "'" .
            COM_getPermSQL('AND');
    $res = DB_query($sql);
    if (DB_numRows($res) == 1) {
        $A = DB_fetchArray($res, false);
        $retval = array(
            'title' => $A['sp_title'],
            'content' => $A['sp_content'],
            'uid' => $A['sp_id'],
            'author' => SRCH_getAuthorName($A['sp_uid']),
            'date' => $A['ts'],
            'hits' => $A['sp_hits'],
            'url' => $_CONF['site_url'] . '/page.php?page=' . urlencode($id),
        );
    }
    return $retval;
}


/**
*   Get the author display name from the user ID.
*   Stores names in a static variable since this can be called many times.
*
*   @param  integer $uid    User ID
*   @return string          Display name
*/
function SRCH_getAuthorName($uid)
{
    static $names = array();
    if (!empty($uid)) {
        if (!isset($names[$uid])) {
            $names[$uid] = COM_getDisplayName($uid);
        }
        return $names[$uid];
    } else {
        return '';
    }
}


/**
*   Index an item when it is saved.
*   First deletes any existing index records, then creates new ones.
*
*   @param  string  $id     Item ID
*   @param  string  $type   Item Type
*/
function plugin_itemsaved_searcher($id, $type)
{
    global $_TABLES;

    $id = DB_escapeString($id);
    switch($type) {
    case 'article':
        USES_searcher_class_indexer();
        $sql = "SELECT sid, uid, title, introtext, bodytext, draft_flag,
                owner_id, group_id,
                perm_owner, perm_group, perm_members, perm_anon
            FROM {$_TABLES['stories']}
            WHERE sid = '$id'";
        $res = DB_query($sql);
        if (DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
            // Remove the doc to clear old records
            \Searcher\Indexer::RemoveDoc($type, $id);
            if ($A['draft_flag'] == 0) {
                \Searcher\Indexer::IndexDoc(array(
                    'item_id' => $A['sid'],
                    'type'  => 'article',
                    'title' => $A['title'],
                    'author' => SRCH_getAuthorName($A['uid']),
                    'content' => $A['introtext'] . ' ' . $A['bodytext'],
                    'perms' => array(
                        'owner_id' => $A['owner_id'],
                        'group_id' => $A['group_id'],
                        'perm_owner' => $A['perm_owner'],
                        'perm_group' => $A['perm_group'],
                        'perm_members' => $A['perm_members'],
                        'perm_anon' => $A['perm_anon'],
                    ),
                ) );
            }
        }
        break;
    case 'staticpages':
        USES_searcher_class_indexer();
        $sql = "SELECT sp_id, sp_title, sp_content,
                owner_id, group_id,
                perm_owner, perm_group, perm_members, perm_anon
                FROM {$_TABLES['staticpage']}
                WHERE sp_id = '$id' AND sp_status = 1 AND sp_search = 1";
        $res = DB_query($sql, 1);
        if (DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
            \Searcher\Indexer::RemoveDoc($type, $id);
            \Searcher\Indexer::IndexDoc(array(
                'item_id' => $A['sp_id'],
                'type'  => $type,
                'title' => $A['sp_title'],
                'content' => $A['sp_content'],
                'perms' => array(
                    'owner_id' => $A['owner_id'],
                    'group_id' => $A['group_id'],
                    'perm_owner' => $A['perm_owner'],
                    'perm_group' => $A['perm_group'],
                    'perm_members' => $A['perm_members'],
                    'perm_anon' => $A['perm_anon'],
                ),
            ) );
        }
        break;
    case 'comments':
    case 'comment':
        USES_searcher_class_indexer();
        $sql = "SELECT cid, sid, uid, title, comment
            FROM {$_TABLES['comments']}
            WHERE cid = '$id'";
        //echo $sql;die;
        $res = DB_query($sql, 1);
        if (DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
            \Searcher\Indexer::RemoveDoc('comment', $A['cid']);
            \Searcher\Indexer::IndexDoc(array(
                'item_id' => $A['cid'],
                'parent_id' => $A['sid'],
                'type'  => 'comment',
                //'title' => $A['title'],   // Don't index comment titles
                'author' => SRCH_getAuthorName($A['uid']),
                'content' => $A['comment'],
            ) );
        }
        break;
     default:
        $id_fld = '';
        break;
    }
}


/**
*   Delete index records for a deleted item.
*
*   @param  string  $id     Item ID
*   @param  string  $type   Item Type, e.g. plugin name
*/
function plugin_itemdeleted_searcher($id, $type)
{
    USES_searcher_class_indexer();
    \Searcher\Indexer::RemoveDoc($type, $id);
}


/**
*   Called from the plugin admin page to re-index all links.
*
*   @return integer     Count of links indexed
*/
function plugin_IndexAll_links()
{
    global $_TABLES;

    USES_searcher_class_indexer();
    $sql = "SELECT lid, title, description,
                owner_id, group_id,
                perm_owner, perm_group, perm_members, perm_anon
            FROM {$_TABLES['links']}";
    //echo $sql;die;
    $res = DB_query($sql, 1);
    \Searcher\Indexer::Removeall('staticpages');
    $count = 0;
    while ($A = DB_fetchArray($res, false)) {
        $count++;
        $props = array(
            'item_id' => $A['lid'],
            'type'  => 'links',
            'title' => $A['title'],
            'content' => $A['description'],
            'perms' => array(
                'owner_id' => $A['owner_id'],
                'group_id' => $A['group_id'],
                'perm_owner' => $A['perm_owner'],
                'perm_group' => $A['perm_group'],
                'perm_members' => $A['perm_members'],
                'perm_anon' => $A['perm_anon'],
            ),
        );
        \Searcher\Indexer::IndexDoc($props);
    }
    return $count;
}


/**
*   Called from the plugin admin page to re-index all staticpages.
*
*   @return integer     Count of articles indexed
*/
function plugin_IndexAll_staticpages()
{
    global $_TABLES;

    USES_searcher_class_indexer();
    $sql = "SELECT sp_id, sp_title, sp_content,
                owner_id, group_id,
                perm_owner, perm_group, perm_members, perm_anon
            FROM {$_TABLES['staticpage']}
            WHERE sp_status = 1 AND sp_search = 1";
    //echo $sql;die;
    $res = DB_query($sql, 1);
    \Searcher\Indexer::Removeall('staticpages');
    $count = 0;
    while ($A = DB_fetchArray($res, false)) {
        $count++;
        $props = array(
            'item_id' => $A['sp_id'],
            'type'  => 'staticpages',
            'title' => $A['sp_title'],
            'content' => $A['sp_content'],
            'perms' => array(
                'owner_id' => $A['owner_id'],
                'group_id' => $A['group_id'],
                'perm_owner' => $A['perm_owner'],
                'perm_group' => $A['perm_group'],
                'perm_members' => $A['perm_members'],
                'perm_anon' => $A['perm_anon'],
            ),
        );
        \Searcher\Indexer::IndexDoc($props);
        plugin_IndexAll_comments('staticpages', $A['sp_id'], $props['perms']);
    }
    return $count;
}


/**
*   Called from plugin_IndexAll_* functions to re-index associated comments
*
*   @param  string  $type   Content type, e.g. "article", "staticpages"
*   @param  mixed   $pid    Parent content item ID
*   @param  mixed   $perms  Permission array from content item, or NULL
*   @return integer     Count of articles indexed
*/
function plugin_IndexAll_comments($type, $pid, $perms=NULL)
{
    global $_TABLES;

    USES_searcher_class_indexer();

    $type = DB_escapeString($type);
    $pid = DB_escapeString($pid);
    $sql = "SELECT cid, sid, uid, title, comment
        FROM {$_TABLES['comments']}
        WHERE type = '$type' AND sid = '$pid'";
    //echo $sql;die;
    $res = DB_query($sql);
    $count = 0;
    // Remove all existing index records for this content
    \Searcher\Indexer::RemoveComments($type, $pid);
    while ($A = DB_fetchArray($res, false)) {
        $count++;
        $props = array(
            'item_id' => $A['cid'],
            'parent_id' => $A['sid'],
            'parent_type' => $type,
            'type'  => 'comment',
            //'title' => $A['title'],
            'author' => SRCH_getAuthorName($A['uid']),
            'content' => $A['comment'],
        );
        if (is_array($perms)) {
            $props['perms'] = array(
                'owner_id' => $perms['owner_id'],
                'group_id' => $perms['group_id'],
                'perm_owner' => $perms['perm_owner'],
                'perm_group' => $perms['perm_group'],
                'perm_members' => $perms['perm_members'],
                'perm_anon' => $perms['perm_anon'],
            );
        }
        \Searcher\Indexer::IndexDoc($props);
    }
    return $count;
}


/**
*   Called from the plugin admin page to re-index all articles.
*
*   @return integer     Count of articles indexed
*/
function plugin_IndexAll_article()
{
    global $_TABLES;

    USES_searcher_class_indexer();

    $sql = "SELECT sid, uid, title, introtext, bodytext,
            owner_id, group_id,
            perm_owner, perm_group, perm_members, perm_anon
        FROM {$_TABLES['stories']}
        WHERE draft_flag = 0";
    //echo $sql;die;
    $res = DB_query($sql);
    $count = 0;
    \Searcher\Indexer::RemoveAll('article');
    while ($A = DB_fetchArray($res, false)) {
        $count++;
        $props = array(
            'item_id' => $A['sid'],
            'type'  => 'article',
            'title' => $A['title'],
            'author' => SRCH_getAuthorName($A['uid']),
            'content' => $A['introtext'] . ' ' . $A['bodytext'],
            'perms' => array(
                'owner_id' => $A['owner_id'],
                'group_id' => $A['group_id'],
                'perm_owner' => $A['perm_owner'],
                'perm_group' => $A['perm_group'],
                'perm_members' => $A['perm_members'],
                'perm_anon' => $A['perm_anon'],
            ),
        );
        \Searcher\Indexer::IndexDoc($props);
        plugin_IndexAll_comments('article', $A['sid'], $props['perms']);
    }
    return $count;
}


/**
*   Service function to be invoked when an item is saved.
*
*   @param  array   $args       Array of item info.
*   @param  mixed   $output     Output to set (not used)
*   @param  mixed   $svc_msg    Service message (not used)
*   @return integer     PLG_RET_OK on success, PLG_RET_ERROR on error
*/
function service_indexDoc_searcher($args, &$output, &$svc_msg)
{
    // Check that the minimum required fields are set
    if (!isset($args['item_id']) || !isset($args['type']) || !isset($args['content'])) {
        return PLG_RET_ERROR;
    }
    USES_searcher_class_indexer();
    \Searcher\Indexer::RemoveDoc($args['type'], $args['item_id']);
    if (!\Searcher\Indexer::IndexDoc($args))
        return PLG_RET_ERROR;
    return PLG_RET_OK;
}


/**
*   PLG function to index a single document
*
*   @param  array   $args       Args, including type, item_id, title, etc.
*   @return boolean     True on success, False on failure
*/
function plugin_indexDoc_searcher($args)
{
    // Check that the minimum required fields are set
    if (!isset($args['item_id']) || !isset($args['type']) ||
        (!isset($args['content']) && !isset($args['title']) && !isset($args['author']))
    ) {
        return false;
    }
    USES_searcher_class_indexer();
    \Searcher\Indexer::RemoveDoc($args['type'], $args['item_id']);
    return \Searcher\Indexer::IndexDoc($args);
}


/**
*   Service function to remove an item from the index.
*   Makes sure that a valid type and item_id are set to remove one item.
*
*   @param  array   $args       Array of item info.
*   @param  mixed   $output     Output to set (not used)
*   @param  mixed   $svc_msg    Service message (not used)
*   @return integer     PLG_RET_OK on success, PLG_RET_ERROR on error
*/
function service_removeDoc_searcher($args, &$output, &$svc_msg)
{
    // Check that the minimum required fields are set
    if (!isset($args['item_id']) || !isset($args['type']) || $args['type'] == 'all') {
        return PLG_RET_ERROR;
    }
    USES_searcher_class_indexer();
    if (!\Searcher\Indexer::RemoveDoc($args['type'], $args['id'])) return PLG_RET_ERROR;
    \Searcher\Indexer::RemoveComments($args['type'], $args['id']);
    return PLG_RET_OK;
}


function plugin_RemoveDoc_searcher($type, $item_id)
{
    USES_searcher_class_indexer();
    if (!\Searcher\Indexer::RemoveDoc($type, $item_id)) return false;
    \Searcher\Indexer::RemoveComments($type, $item_id);
    return true;
}


/**
*   Service function to remove all items for a plugin from the index.
*   May be called during plugin removal.
*   Makes sure that a valid type is set to remove all items for a single type.
*
*   @param  array   $args       Array of item info.
*   @param  mixed   $output     Output to set (not used)
*   @param  mixed   $svc_msg    Service message (not used)
*   @return integer     PLG_RET_OK on success, PLG_RET_ERROR on error
*/
function service_removeAll_searcher($args, &$output, &$svc_msg)
{
    // Check that the minimum required fields are set. Don't allwow plugins
    // to accidentally delete all.
    if (!isset($args['type']) || $args['type'] == 'all') {
        return PLG_RET_ERROR;
    }
    USES_searcher_class_indexer();
    if (!\Searcher\Indexer::RemoveAll($type)) return PLG_RET_ERROR;
    \Searcher\Indexer::RemoveComments($type);
    return PLG_RET_OK;
}

function plugin_removeAll_searcher($type)
{
    // Check that the minimum required fields are set. Don't allwow plugins
    // to accidentally delete all.
    if (empty($type) || $type == 'all') {
        return false;
    }
    USES_searcher_class_indexer();
    if (!\Searcher\Indexer::RemoveAll($type)) return false;
    \Searcher\Indexer::RemoveComments($type);
    return true;
}


/**
*   Selection dropdown to pick the stemmer in the configuration manager.
*
*   @return Array Associative array of section_name=>section_id
*/
function plugin_configmanager_select_stemmer_searcher()
{
    global $LANG_SRCH;

    $A = array($LANG_SRCH['none'] => '');
    // Collect the available stemmers
    $results = @glob(__DIR__ . '/classes/stemmer/*.class.php');
    $installable = '';
    if (is_array($results)) {
        foreach ($results as $fullpath) {
            $parts = explode('/', $fullpath);
            list($class,$x1,$x2) = explode('.', $parts[count($parts)-1]);
            $A[$class] = $class;
        }
    }
    return $A;
}

?>
