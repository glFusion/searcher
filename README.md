# Searcher plugin for glFusion
## Overview
This plugin is an attempt to provide an improved search function for glFusion
using one or more index tables to allow fulltext-type searching without
requiring a fulltext index.

Much of the internal mechanics is yet TBD. Currently the article content type
is supported, and requires manually running "makeindex.php" from the plugin
directory.

## Configuration
### Main Settings
#### Minimum Word Length to Consider
Enter the minimum number of letters to be considered a "word". Words with less
than this number of letters will not be added to the index.

#### Results to show per page
Enter the number of search results to show on each page.

#### Length of excerpt to display
Enter the number of words to display in the excerpt on the search results page.

#### Maximum occurrences of a term to count
Enter the maximum number of times that a single word or phrase will be counted
in a page when calculating the weights. This is to keep some pages which may
have many occurrences of a single word from always appearing at the top of the
search results, outweighing other pages that may be more relevant.

### Weighting
Different weights can be assigned to words that appear in different fields.
For example, you might want to give a higher weight, or priority, to search
terms that appear in the title than in the content or author name.

Higher weights will cause the articles to float higher in the search results.

## Service functions
Service functions are called as ```PLG_invokeService('searcher', function, $args, $output, $svc_msg);```
At this time the $output and $svc_msg variables are not used.

Functions and the required arguments array are listed below:

### indexDoc
Adds a single document to the index. Also indexes any comments.

The item will not be indexed at all unless at least one of Title, Content or
Author is not empty.
```
$args = array(
    'item_id'   => the item ID in its database table (required)
    'type'      => Type of item, e.g. "article" or the plugin name (required)
    'content'   => Full content to index
    'title'     => Item title
    'author'    => Display name of the author, not the user ID
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
Author and Title are also optional. Permission fields are added to every record in the index
so a guest will only see results that they can actually access.

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
This should be called as part of a plugin removal.
```
$args = array(
    'type'      => Type of item, e.g. plugin name (required)
);
```

