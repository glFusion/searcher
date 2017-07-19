<?php
/**
*   Creates an index of stories for the Searcher plugin.
*   This is a test script to initialize the index manually
*/
namespace Searcher;
require_once '../../../public_html/lib-common.php';
require_once 'searcher.php';
require_once 'classes/Indexer.class.php';

$sql = "SELECT sid, uid, title, introtext, bodytext,
            owner_id, group_id,
            perm_owner, perm_group, perm_members, perm_anon
        FROM {$_TABLES['stories']}
        WHERE draft_flag = 0";
//echo $sql;die;
$res = DB_query($sql);
echo "starting...\n";
while ($A = DB_fetchArray($res, false)) {
    echo "Indexing {$A['title']}\n";
    Indexer::IndexDoc(array(
        'item_id' => $A['sid'],
        'type'  => 'article',
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
echo "\n";
