# Searcher plugin for glFusion
## Overview
This plugin provides an improved search function for glFusion
using an index table to allow fulltext-type searching without
requiring a fulltext index.

## Preparation for Use
Before the Searcher plugin can be used, the initial index must be created.
After this is done any content changes will automatically update the index.

1. Go through the configuration options below and set them according to your preferences.
1. Create the initial index
    1. Visit `{site_url}/admin/plugins/searcher/index.php`
    1. Click "Reindex Content"
    1. Select all the available content types
    1. Click the "Reindex" button. This will take some time but uses AJAX to prevent browser timeouts.

If a previously-installed plugin is updated to a version that supports glFusion 1.7.0, you will want to revisit the indexing page again and reindex content for that plugin.

## Searching
Visit `{site_url}/searcher/index.php` and enter a search phrase.
Click the "Advanced Options" button to make available other options:
* Limit to one content type
* Limit to recent items
* Search any combination of Title, Author Name or Content fields

By default, all content types and all fields are searched.

Once the plugin is confirmed to be working correctly, re-visit the global configuration and set "Replace Stock glFusion Search" to "Yes".
At this point the default search box in the header as well as the standard `/search.php` URL will utilize the Searcher plugin.

## Configuration
### Main Settings
#### Minimum Word Length to Consider
Enter the minimum number of letters to be considered a "word". Words with fewer
than this number of letters will not be added to the index.

Reducing this number may catch more search results but will increase the index size.

Default: 3

#### Maximum Words in a Phrase
When tokens are created from a document or search query, words can be grouped
into phrases. The phrasing is simply "word word+1 word+2" etc. Longer phrases
can provide more accurate search results by considering matched phrases to be
more relevant than single matched words, but a higher value here will significantly
increase the time required to index your content as well as the database size.

Default: 3

#### Results to show per page
Enter the number of search results to show on each page.

Default: 20

#### Length of excerpt to display
Enter the number of words to display in the excerpt on the search results page.

Default: 50

#### Maximum occurrences of a term to count
Enter the maximum number of times that a single word or phrase will be counted
in a page when calculating the weights. This is to keep some pages which may
have many occurrences of a single word from always appearing at the top of the
search results, outweighing other pages that may be more relevant.

Default: 5

#### Show author name in results
You can select to include the author's name in the search results, with or
without a link, or hide the name altogether.

Default: Yes, with link

#### Stemmer (Experimental)
This plugin includes an adaptation of the [Porter language stemmer](https://tartarus.org/martin/PorterStemmer/index.html)
to determine the roots of words. To use this select `Porter_en`, otherwise
select `None` (the default).

The stemmer is experimental and may lead to odd results but will also include
results based on word variations such as plurals. You must regenerate all
indexes if this option is changed.

Default: None

#### Ignore Auto Tags
If selected, then autotags are removed completely before generating indexes.
This will prevent the raw content of autotags from causing pages to appear
in the search results where the page does not visibly contain the search terms.

Regardless of this setting, autotags are not processed before indexing or searching.

Default: False

#### Replace stock glFusion search
If selected then the Searcher plugin will be used by glFusion's search.php file,
replacing the stock search engine. If this is set to "No", then the normal
glFusion search will be used but the Search plugin can still be accessed at
`{site_url}/searcher/index.php`.

Default: False

### Weighting
Different weights can be assigned to words that appear in different fields.
For example, you might want to give a higher weight, or priority, to search
terms that appear in the title than in the content or author name.

Higher weights will cause the articles to float higher in the search results.

Defaults:
    Title: 1.5
    Author: 1.2
    Content: 1

## API functions
API functions are called using ```PLG_callFunctionForOnePlugin(function, $args);```

The Searcher plugin also provides functions to handle ItemSaved and ItemDeleted
events which should be sufficient. The functions below can be called in special
cases.

Functions and the required arguments array are listed below:

### plugin_indexDoc_searcher($args = array())
Adds a single document to the index. Also indexes any comments.

At least one of "content", "title" or "author" must not be empty.

Usage:
```
$args = array(
    1 => array(
        'item_id'   => the item ID in its database table (required)
        'type'      => Type of item, e.g. "article" or the plugin name (required)
        'content'   => Full content to index
        'title'     => Item title
        'author'    => Display name of the author, not the user ID
        'date'      => Unix timestamp, defaults to "now" if not provided
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

