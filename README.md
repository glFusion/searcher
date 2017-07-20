# Searcher plugin for glFusion
## Overview
This plugin is an attempt to provide an improved search function for glFusion
using one or more index tables to allow fulltext-type searching without
requiring a fulltext index.

Much of the internal mechanics is yet TBD. Currently the article content type
is supported, and requires manually running "makeindex.php" from the plugin
directory.

## Service functions
Service functions are called as ```PLG_invokeService('searcher', function, $args, $output, $svc_msg);```
At this time the $output and $svc_msg variables are not used.

Functions and the required arguments array are listed below:

### indexDoc
Adds a single document to the index. Also indexes any comments.
```
$args = array(
    'item_id'   => the item ID in its database table required)
    'type'      => Type of item, e.g. "article" or the plugin name (required)
    'content'   => Full content to index (required)
    'title'     => Item title
    'author'    => Numeric user id of the author
    'perms' => array(
        'owner_id'      => Numeric user id of the owner
        'group_id'      => Numeric user id of the gruop
        'perm_owner'    => Owner permission
        'perm_group'    => Group permission
        'perm_members'  => Members permission
        'perm_anon'     => Anonymous user permission
    ),
);
```
If the "perms" element is not provided or is NULL then the permissions allow read access to everyone.
Author and Title are also optional.

### removeDoc
Removes a single document from the index, along with associated comments
```
$args = array(
    'item_id'   => Database of the item being removed (required)
    'type'      => Type of item, e.g. plugin name (required)
);
```

### removeAll
Removes all itms of a particular type from the index, along with any comments.
```
$args = array(
    'type'      => Type of item, e.g. plugin name (required)
);
```

