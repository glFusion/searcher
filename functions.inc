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

    if (SEC_inGroup('Root') && GVERSION < '1.6.1' && $_CONF['allow_mysql_dump']) {
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

    if (SEC_inGroup('Root') && GVERSION < '1.6.1' && $_CONF['allow_mysql_dump']) {
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
            'url' => $_CONF['site_url'] . '/page.php?id=' . urlencode($id),
        );
    }
    return $retval;
}

function SRCH_getAuthorName($uid)
{
    static $names = array();
    if (!isset($names[$uid])) {
        $names[$uid] = COM_getDisplayName($uid);
    }
    return $names[$uid];
}


function plugin_itemsaved_searcher($id, $type)
{
    global $_TABLES;

    $id = DB_escapeString($id);
    switch($type) {
    case 'article':
        USES_searcher_class_indexer();
        $sql = "SELECT sid, uid, title, introtext, bodytext,
                owner_id, group_id,
                perm_owner, perm_group, perm_members, perm_anon
            FROM {$_TABLES['stories']}
            WHERE sid = '$id' AND draft_flag = 0";
        $res = DB_query($sql);
        if (DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
            \Searcher\Indexer::RemoveDoc($type, $id);
            \Searcher\Indexer::IndexDoc(array(
                'item_id' => $A['sid'],
                'type'  => 'article',
                'title' => $A['title'],
                'author' => COM_getDisplayName($A['uid']),
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
     default:
        $id_fld = '';
        break;
    }
}


function plugin_itemdeleted_searcher($id, $type)
{
    switch ($type) {
    case 'article':
    case 'staticpage':
    case 'staticpages':
        USES_searcher_class_indexer();
        \Searcher\Indexer::RemoveDoc($type, $id);
        break;
    default:
        break;
    }
}

?>
