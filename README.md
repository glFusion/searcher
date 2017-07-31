# Searcher plugin for glFusion
## Overview
This plugin is an attempt to provide an improved search function for glFusion
using one or more index tables to allow fulltext-type searching without
requiring a fulltext index.

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

#### Show author name in results
You can select to include the author's name in the search results, with or
without a link, or hide the name altogether.

#### Stemmer (Experimental)
This plugin includes an adaptation of the Porter language stemmer to determine
the roots of words. To use this select "Porter_en", otherwise select "None"
(the default).

The stemmer is experimental and may lead to odd results but will also include
results based on word variations such as plurals. You must regenerate all
indexes if this option is changed.

### Weighting
Different weights can be assigned to words that appear in different fields.
For example, you might want to give a higher weight, or priority, to search
terms that appear in the title than in the content or author name.

Higher weights will cause the articles to float higher in the search results.

## API functions
API functions are called using ```PLG_callFunctionForOnePlugin(function, $args);```

Functions and the required arguments array are listed below:

### plugin_indexDoc_searcher($args = array())
Adds a single document to the index. Also indexes any comments.

Usage:
```
$args = array(
    1 => array(
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
    ),
);
PLG_callFunctionForOnePlugin('plugin_indexDoc_searcher', $args);
```
The item will not be indexed at all unless at least one of Title, Content or
Author is not empty.

If the "perms" element is not provided or is NULL then the permissions allow read access to everyone.
Permission fields are added to every record in the index so a guest will only see results that
they can actually access.

### plugin_RemoveDoc_searcher($type, $item_id)
Removes a single document from the index, along with associated comments.
Arguments are a simple array of item type and database ID
```
$args = array(
    Type of item, e.g. plugin name (required),
    Database ID of the item being removed (required),
);
PLG_callFunctionForOnePlugin('plugin_removeDoc_searcher', $args);
```

### plugin_removeAll_searcher($type)
Removes all itms of a particular type from the index, along with any comments.
This should be called as part of a plugin removal.
```
$args = array(
    'type'      => Type of item, e.g. plugin name (required)
);
PLG_callFunctionForOnePlugin('plugin_removeAll_searcher', $args);
```

