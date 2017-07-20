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
    *   @param  array   $content    Array of text elements:
    *               'author', 'content' currently supported
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
            // index content
            $tokens = self::Tokenize($content[$fld]);
            foreach ($tokens as $token=>$count) {
                isset($insert_data[$token][$fld]) ?
                $insert_data[$name][$fld] += $count : $insert_data[$token][$fld] = $count;
            }
        }

        $item_id = DB_escapeString($content['item_id']);
        $type = DB_escapeString($content['type']);
        $values = array();
        foreach ($insert_data as $term => $data) {
            foreach (self::$fields as $var=>$weight) {
                $$var = isset($data[$var]) ? (int)$data[$var] : 0;
            }
            $term = DB_escapeString($term);
            if (isset($data['perms']) && is_array($data['perms'])) {
                $owner_id = (int)$data['perms']['owner_id'];
                $group_id = (int)$data['perms']['group_id'];
                $perm_owner = (int)$data['perms']['perm_owner'];
                $perm_group = (int)$data['perms']['perm_group'];
                $perm_members = (int)$data['perms']['perm_members'];
                $perm_anon = (int)$data['perms']['perm_anon'];
            } else {
                // No permission restrictions. Only read is needed here.
                $owner_id = 1;
                $group_id = 2;
                $perm_owner = 2;
                $perm_group = 2;
                $perm_members = 2;
                $perm_anon = 2;
            }
            $values[] = "('$type', '$item_id', '$term', $content, $title, $author,
                    $owner_id, $group_id, $perm_owner, $perm_group, $perm_members, $perm_anon)";
        }

        $values = implode(', ', $values);
        $sql = "INSERT IGNORE INTO {$_TABLES['searcher_index']}
                (type, item_id, term, content, title, author,
                owner_id, group_id, perm_owner, perm_group,
                perm_members, perm_anon)
                VALUES $values";
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

}

?>
