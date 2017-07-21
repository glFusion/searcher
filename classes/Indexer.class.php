<?php
/**
*   Maintain an index table for the Searcher plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    searcher
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Searcher;
require_once __DIR__ . '/Common.class.php';

/**
*   Indexing class
*   @package searcher
*/
class Indexer extends Common
{

    /**
    *   Index a single document
    *
    *   @param  array   $content    Array of item elements
    *   @return boolean     True on success, False on DB error
    */
    public static function IndexDoc($content)
    {
        global $_TABLES;

        if (self::$stopwords === NULL) {
            self::Init();
        }

        $insert_data = array();     // data to be inserted into DB
        foreach(self::$fields as $fld=>$weight) {
            // index content fields and get a count of tokens
            $tokens = self::Tokenize($content[$fld]);
            foreach ($tokens as $token=>$data) {
                if (isset($insert_data[$token][$fld])) {
                    $insert_data[$token][$fld]['count'] += $data['count'];
                } else {
                    $insert_data[$token]['weight'] = $data['weight'];
                    $insert_data[$token][$fld]['count'] = 1;
                }
            }
        }

        $item_id = DB_escapeString($content['item_id']);
        $type = DB_escapeString($content['type']);
        $parent_id = isset($content['parent_id']) ?
            DB_escapeString($content['parent_id']) : $item_id;
        $parent_type = isset($content['parent_type']) ?
            DB_escapeString($content['parent_type']) : $type;
        if (isset($content['perms']) && is_array($content['perms'])) {
            $owner_id = (int)$content['perms']['owner_id'];
            $group_id = (int)$content['perms']['group_id'];
            $perm_owner = (int)$content['perms']['perm_owner'];
            $perm_group = (int)$content['perms']['perm_group'];
            $perm_members = (int)$content['perms']['perm_members'];
            $perm_anon = (int)$content['perms']['perm_anon'];
        } else {
            // No permission restrictions. Only read is needed here.
            $owner_id = 1;
            $group_id = 2;
            $perm_owner = 2;
            $perm_group = 2;
            $perm_members = 2;
            $perm_anon = 2;
        }

        $values = array();
        foreach ($insert_data as $term => $data) {
            foreach (self::$fields as $var=>$weight) {
                $$var = isset($data[$var]) ? (int)$data[$var]['count'] : 0;
            }
            $term = DB_escapeString($term);
            $weight = (float)$data['weight'];
            $values[] = "('$type', '$item_id', '$term', '$parent_id', '$parent_type',
                    $content, $title, $author, $owner_id, $group_id,
                    $perm_owner, $perm_group, $perm_members, $perm_anon,
                    $weight)";
        }

        $values = implode(', ', $values);
        $sql = "INSERT IGNORE INTO {$_TABLES['searcher_index']}
                (type, item_id, term, parent_id, parent_type, content, title, author,
                owner_id, group_id, perm_owner, perm_group,
                perm_members, perm_anon, weight)
                VALUES $values";
        /*if ($type == 'comment') {
        echo $sql;die;
        }*/
        $res = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("Searcher Error Indexing $type, ID $item_id");
            return false;
        } else {
            return true;
        }
    }


    /**
    *   Remove a document from the index
    *   Deletes all records that match $type and $item_id
    *
    *   @param  string  $type       Type of document
    *   @param  string  $item_id    Document ID
    *   @return boolean     True on success, False on failure
    */
    public static function RemoveDoc($type, $item_id)
    {
        global $_TABLES;

        DB_delete($_TABLES['searcher_index'],
                array('type', 'item_id'),
                array($type, $item_id) );
        if (DB_error()) {
            COM_errorLog("Searcher: Error removing $type, ID $item_id");
            return false;
        } else {
            return true;
        }
    }


    /**
    *   Remove all index records, normally of a specific type.
    *   Specify "all" as the type to truncate the table.
    *
    *   @param  string  $type   Type (article, staticpages, etc.)
    */
    public static function RemoveAll($type = 'all')
    {
        global $_TABLES;

        if ($type === 'all') {
            DB_query("TRUNCATE {$_TABLES['searcher_index']}");
        } else {
            DB_delete($_TABLES['searcher_index'], 'type', $type);
        }
    }


    /**
    *   Remove all comments for a specific parent type/id
    *   Specify 'all' as the item ID to remove all comments for all
    *   content items of type $type.
    *
    *   @param  string  $type       Type of content (article, staticpage, etc.)
    *   @param  mixed   $item_id    ID of article, page, etc.
    */
    public static function RemoveComments($parent_type, $item_id=NULL)
    {
        global $_TABLES;

        $params = array('type', 'parent_type');
        $values = array('comment', $parent_type);
        if ($item_id !== NULL) {
            $params[] = 'parent_id';
            $values[] = $item_id;
        }
        DB_delete($_TABLES['searcher_index'], $params, $values);
    }

}

?>
