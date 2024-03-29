<?php
/**
 * Maintain an index table for the Searcher plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2020 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v1.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher;


/**
 * Indexing class.
 * @package searcher
 */
class Indexer extends Common
{

    /**
     * Index a single document.
     *
     * @param   array   $content    Array of item elements
     * @param   boolean $use_queue  True to queue the DB inserts
     * @return  boolean     True on success, False on DB error
     */
    public static function IndexDoc($content, $use_queue = false)
    {
        global $_SRCH_CONF;

        if (empty(self::$stopwords)) {
            self::Init();
        }

        $values = SESS_getVar('searcher_queue');
        if ($values === 0) {
            $values = array();
        }

        // Remove autotags if so configured and the content field is used.
        // There's a small chance that only title and/or author are used here.
        if (
            $_SRCH_CONF['ignore_autotags'] &&
            isset($content['content']) &&
            !empty($content['content'])
        ) {
            $content['content'] = self::removeAutoTags($content['content']);
        }
        // Strip the specified tags
        $content['content'] = self::stripTags($content['content'], false);

        // Set author name for the index if not provided and author field
        // is a numeric ID
        if ( (!isset($content['author_name']) || empty($content['author_name']) )
              && is_numeric($content['author']) && $content['author'] > 0
        ) {
            $content['author_name'] = COM_getDisplayName($content['author']);
        }

        $insert_data = array();     // data to be inserted into DB
        foreach(self::$fields as $fld=>$weight) {
            // index content fields and get a count of tokens
            if ( isset($content[$fld])) {
                if ($fld == 'author') {
                    // hack to get the author name into the "author" index
                    $tokens = self::Tokenize($content['author_name']);
                } else {
                    $tokens = self::Tokenize($content[$fld]);
                }
                foreach ($tokens as $token=>$data) {
                    if (isset($insert_data[$token])) {
                        $insert_data[$token][$fld] = $data['count'];
                    } else {
                        $insert_data[$token] = array(
                            'weight' => $data['weight'],
                            $fld => $data['count'],
                        );
                    }
                }
            }
        }
        $item_id = DB_escapeString($content['item_id']);
        $type = DB_escapeString($content['type']);
        $parent_id = isset($content['parent_id']) && !empty($content['parent_id']) ?
            DB_escapeString($content['parent_id']) : $item_id;
        $parent_type = isset($content['parent_type']) && !empty($content['parent_type']) ?
            DB_escapeString($content['parent_type']) : $type;
        $ts = isset($content['date']) ? (int)$content['date'] : time();
        $owner_id = isset($content['author']) ? (int)$content['author'] : 0;
        $grp_access = 2;    // default to all users access if no perms sent
        if (isset($content['perms']) && is_array($content['perms'])) {
            if ($content['perms']['perm_anon'] >= 2) {
                $grp_access = 2;    // anon users
            } elseif ($content['perms']['perm_members'] >= 2) {
                $grp_access = 13;   // loged-in users
            } elseif (
                $content['perms']['perm_group'] >= 2 &&
                !empty($content['perms']['group_id'])
            ) {
                // limit to specific group
                $grp_access = (int)$content['perms']['group_id'];
            } else {
                // limit to root if no other group can view
                $grp_access = 1;
            }
        }

        $insertCount = count($values);
        foreach ($insert_data as $term => $data) {
            foreach (self::$fields as $var=>$weight) {
                $$var = isset($data[$var]) ? (int)$data[$var] : 0;
            }
            $term = DB_escapeString(trim($term));
            $weight = (float)$data['weight'];
            $values[] = "(" .
                "'$type','$item_id','$term','$parent_id','$parent_type'," .
                "$ts,$content,$title,$author,$owner_id,$grp_access,$weight" .
                ")";
            $insertCount++;

            if ( $insertCount > 2000 ) {
                // Write out the values so far and reset the values array
                self::FlushQueue($values);
                $values = array();
                $insertCount = 0;
            }
        }
        if ($use_queue) {
            SESS_setVar('searcher_queue', $values);
            return true;
        } else {
            return self::FlushQueue($values);
        }
    }


    /**
     * Save the search data in the DB.
     * If provided, $values is expectedd to be an array of ('x', 'y', ...)
     * items that will be concatenated into a single SQL statement.
     * If $values is not provided then the values are obtained from the
     * session var.
     * Values must already be SQL-safe.
     *
     * @param   mixed   $values     Array of value clauses, NULL if not used
     */
    public static function FlushQueue($values = NULL)
    {
        global $_TABLES;

        if ($values === NULL) {     // get values from session var
            $values = SESS_getVar('searcher_queue');
        }
        if (empty($values)) return true;    // nothing to do

        $values = implode(', ', $values);
        $sql = "INSERT IGNORE INTO {$_TABLES['searcher_index']} (
                    type, item_id, term, parent_id, parent_type, ts,
                    content, title, author, owner_id, grp_access, weight
                ) VALUES $values";
        $res = DB_query($sql);
        SESS_setVar('searcher_queue', array());
        if (DB_error()) {
            COM_errorLog(__NAMESPACE__ . '::' . __FUNCTION__ . "- Indexing Error: $sql");
            return false;
        } else {
            return true;
        }
    }


    /**
     * Remove a document from the index.
     * Deletes all records that match $type and $item_id.
     *
     * @param   string  $type       Type of document
     * @param   mixed   $item_id    Document ID, single or array
     * @return  boolean     True on success, False on failure
     */
    public static function RemoveDoc($type, $item_id)
    {
        global $_TABLES;

        if (!self::_indexExists()) {
            return true;
        }

        if ($item_id == '*') {
            return self::RemoveAll($type);
        } elseif (is_array($item_id)) {
            $item_id_str = implode("','", array_map('DB_escapeString', $item_id));
        } else {
            $item_id_str = DB_escapeString($item_id);
        }
        $type = DB_escapeString($type);

        $sql = "DELETE FROM {$_TABLES['searcher_index']}
                WHERE type = '$type'
                AND item_id IN ('$item_id_str')";
        DB_query($sql);
        if (DB_error()) {
            COM_errorLog(__NAMESPACE__ . '::' . __FUNCTION__ . "- Error removing $type, ID $item_id_str");
            return false;
        } else {
            return self::RemoveComments($type, $item_id_str, true);
        }
    }


    /**
     * Remove all index records, normally of a specific type.
     * Specify "all" as the type to truncate the table.
     *
     * @param   string  $type   Type (article, staticpages, etc.)
     * @return  boolean     True on success, False on DB error
     */
    public static function RemoveAll($type = 'all')
    {
        global $_TABLES;

        if (!self::_indexExists()) {
            return true;
        }

        if ($type === 'all') {
            DB_query("TRUNCATE {$_TABLES['searcher_index']}");
        } else {
            DB_delete($_TABLES['searcher_index'], 'type', $type);
            self::RemoveComments($type);
        }
        return DB_error() ? false : true;
    }


    /**
     * Remove all comments for a specific parent type and optional id.
     * $item_id may be a single value or an array, leave as NULL to remove
     * all comments for all content items of type $type.
     *
     * @param   string  $parent_type    Type of content (article, staticpage, etc.)
     * @param   mixed   $item_id        ID of article, page, etc.
     * @param   boolean $sanitized      True if the item_id is already SQL-safe
     * @return  boolean             True on success, False on failure
     */
    public static function RemoveComments($parent_type, $item_id=NULL, $sanitized=false)
    {
        global $_TABLES;

        if ( ! self::CommentsEnabled() ) {
            return true;
        }

        $parent_type = DB_escapeString($parent_type);
        $sql = "DELETE FROM {$_TABLES['searcher_index']}
                WHERE type = 'comment'
                AND parent_type = '$parent_type' ";
        if (is_array($item_id)) {
            $item_id = implode("','", array_map('DB_escapeString', $item_id));
            $sql .= "AND item_id IN ('$item_id')";
        } elseif ($item_id !== NULL) {
            if (!$sanitized) $item_id = DB_escapeString($item_id);
            $sql .= "AND item_id IN ('$item_id')";
        }
        DB_query($sql);
        if (DB_error()) {
            COM_errorLog(__NAMESPACE__ . '::' . __FUNCTION__ . "- Error: $parent_type, ID $item_id");
            return false;
        } else {
            return true;
        }
    }


    /**
     * Protect against DB errors if an index function is called after the table
     * has been removed. This may happen during callbacks during plugin removal.
     *
     * @return  boolean     Results from DB_checkTableExists()
     */
    private static function _indexExists()
    {
        static $exists = NULL;

        if ($exists === NULL) {
            $exists = DB_checkTableExists('searcher_index');
        }
        return $exists;
    }


    /**
     * Index a single document by the ID and type.
     * Called from plugin_itemsaved_searcher() and when running from the CLI.
     *
     * @param   mixed   $id     Document ID
     * @param   string  $type   Document type or plugin name
     * @param   mixed   $old_id Old document ID, used if it was renamed.
     * @return  integer     Plugin return code
     */
    public static function indexById($id, $type, $old_id='')
    {
        $contentInfo = PLG_getItemInfo(
            $type, $id,
            'id,date,parent_type,parent_id,title,searchidx,author,author_name,hits,perms,search_index,status',
            2
        );

        // Document is always removed before indexing anyway,
        // just remove it here in case contentInfo is invalid.
        if ( $old_id != '' && $id != $old_id ) {
            self::RemoveDoc($type, $old_id);
        }
        // Always remove the document being index to start fresh
        self::RemoveDoc($type, $id);

        if (
            !is_array($contentInfo) ||
            count($contentInfo) < 1 ||
            !isset($contentInfo['searchidx']) ||
            empty($contentInfo['searchidx'])
        ) {
            return PLG_RET_ERROR;
        }

        if ($type == 'comment') {
            // For comments, get the parent item's permissions as "root".
            $parent = PLG_getItemInfo(
                $contentInfo['parent_type'],
                $contentInfo['parent_id'],
                'id,perms',
                2
            );
            if (is_array($parent) && isset($parent['perms']) && is_array($parent['perms'])) {
                $contentInfo['perms'] = $parent['perms'];
            }
        }
    
        // If no permissions returned, use defaults
        if (!isset($contentInfo['perms']) || empty($contentInfo['perms'])) {
            $contentInfo['perms'] = array(
                'owner_id' => 2,
                'group_id' => 1,
                'perm_owner' => 3,
                'perm_group' => 2,
                'perm_members' => 2,
                'perm_anon' => 2,
            );
        }
        // If an "enabled" status isn't returned by the plugin, assume enabled
        if (!isset($contentInfo['status']) || is_null($contentInfo['status'])) {
            $contentInfo['status'] = 1;
        }

        $props = array(
            'item_id' => $contentInfo['id'],
            'type'  => $type,
            'author' => $contentInfo['author'],
            'author_name' => $contentInfo['author_name'],
            // Hack to avoid indexing comment titles which don't show anyway
            'title' => $type == 'comment' ? NULL : $contentInfo['title'],
            'content' => $contentInfo['searchidx'],
            'date' => $contentInfo['date'],
            'perms' => $contentInfo['perms'],
            'parent_id' => $contentInfo['parent_id'],
            'parent_type' => $contentInfo['parent_type'],
        );
        if ($contentInfo['status']) {
            // Index only if status is nonzero (i.e. not draft or disabled)
            self::IndexDoc($props);
        }
        return PLG_RET_OK;
    }

}
