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
        global $_TABLES, $_SRCH_CONF;

        if (empty(self::$stopwords)) {
            self::Init();
        }

        // Remove autotags if so configured and the content field is used.
        // There's a small chance that only title and/or author are used here.
        if ($_SRCH_CONF['ignore_autotags'] &&
            isset($content['content']) && !empty($content['content'])) {
            $content['content'] = self::removeAutoTags($content['content']);
        }

        // Set author name for the index if not provided and author field
        // is a numeric ID
        if ( (!isset($content['author_name']) || empty($content['author_name']) )
              && is_numeric($content['author']) && $content['author'] > 0
        ) {
            $content['author_name'] = COM_getDisplayName($content['author']);
        }
        // TODO: uid is not currently used, but save it anyway for future use.
        // Indexer uses 'author' for the author name.
        // May save numeric author ID in the future...
        //$content['uid'] = (int)$content['author'];
        $content['author'] = $content['author_name'];

        $insert_data = array();     // data to be inserted into DB
        foreach(self::$fields as $fld=>$weight) {
            // index content fields and get a count of tokens
            if ( isset($content[$fld])) {
                $tokens = self::Tokenize($content[$fld]);
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
        $grp_access = 2;    // default to all users access if no perms sent
        if (isset($content['perms']) && is_array($content['perms'])) {
            if ($content['perms']['perm_anon'] == 2) {
                $grp_access = 2;    // anon users
            } elseif ($content['perms']['perm_members'] == 2) {
                $grp_access = 13;   // loged-in users
            } elseif (!empty($content['perms']['group_id'])) {
                // limit to specific group
                $grp_access = (int)$content['perms']['group_id'];
            }
        }

        $values = array();
        $insertCount = 0;
        foreach ($insert_data as $term => $data) {
            foreach (self::$fields as $var=>$weight) {
                $$var = isset($data[$var]) ? (int)$data[$var] : 0;
            }
            $term = DB_escapeString(trim($term));
            $weight = (float)$data['weight'];
            $values[] = "('$type', '$item_id', '$term', '$parent_id', '$parent_type',
                    $ts, $content, $title, $author, $grp_access, $weight)";
            $insertCount++;

            if ( $insertCount > 5000 ) {
                $values = implode(', ', $values);
                $sql = "INSERT IGNORE INTO {$_TABLES['searcher_index']} (
                        type, item_id, term, parent_id, parent_type, ts,
                        content, title, author, grp_access, weight
                        ) VALUES $values";
                //echo $sql;die;
                $res = DB_query($sql);
                if (DB_error()) {
                    COM_errorLog("Searcher Error Indexing $type, ID $item_id");
                    return false;
                }
                $values = array();
                $insertCount = 0;
            }
        }
        if (empty($values)) {
            return true;
        }
        $values = implode(', ', $values);
        $sql = "INSERT IGNORE INTO {$_TABLES['searcher_index']} (
                type, item_id, term, parent_id, parent_type, ts,
                content, title, author, grp_access, weight
                ) VALUES $values";
        //echo $sql;die;
        $res = DB_query($sql);
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

        if ($item_id == '*') {
            return self::RemoveAll($type);
        }

        DB_delete($_TABLES['searcher_index'],
                array('type', 'item_id'),
                array($type, $item_id) );
        if (DB_error()) {
            COM_errorLog("Searcher: Error removing $type, ID $item_id");
            return false;
        } else {
            return self::RemoveComments($type, $item_id);
        }
    }


    /**
    *   Remove all index records, normally of a specific type.
    *   Specify "all" as the type to truncate the table.
    *
    *   @param  string  $type   Type (article, staticpages, etc.)
    *   @return boolean     True on success, False on DB error
    */
    public static function RemoveAll($type = 'all')
    {
        global $_TABLES;
        if ($type === 'all') {
            DB_query("TRUNCATE {$_TABLES['searcher_index']}");
        } else {
            DB_delete($_TABLES['searcher_index'], 'type', $type);
            self::RemoveComments($type);
        }
        return DB_error() ? false : true;
    }


    /**
    *   Remove all comments for a specific parent type/id
    *   Leave the item ID as NULL to remove all comments for all
    *   content items of type $type.
    *
    *   @param  string  $type       Type of content (article, staticpage, etc.)
    *   @param  mixed   $item_id    ID of article, page, etc.
    *   @return boolean             True on success, False on failure
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
        if (DB_error()) {
            COM_errorLog("Searcher RemoveComments Error: $parent_type, ID $item_id");
            return false;
        } else {
            return true;
        }
    }

}

?>
