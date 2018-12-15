<?php
/**
 * Default English Language file for the Searcher plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017 Lee Garner
 * @package     searcher
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
global $_CONF;

/**
 * The plugin's lang array.
 * @global array $LANG_SRCH
 */
$LANG_SRCH = array(
    'menu_label'    => 'Searcher',
    'generate_all'  => 'Generate All',
    'adm_counters'  => 'Search Terms',
    'regenerate'    => 'Regenerate Indexes',
    'version'       => 'Version',
    'find'          => 'Find',
    'in'            => 'in',
    'none'          => 'None',
    'search_terms'  => 'Search Terms',
    'queries'       => 'Queries',
    'results'       => 'Results',
    'clear_counters' => 'Clear Counters',
    'search_title'  => 'Search Title',
    'search_author' => 'Search Author',
    'search_content'=> 'Search Content',
    'query_too_short' => 'Query must contain at least %d letters',
    'showing_results' => 'Showing %1$d - %2$d of %3$d Results',
    'hlp_gen_all'   => 'Re-generate all indexes for the selected content types. Use this option after installing the plugin, or after changing certain key configuration items such as the minimum word length or changing the stemmer.',
    'hlp_counters'  => 'Here are the search queries made by site visitors, along with the number of times each query was made.',
    'hlp_reindex'   => 'Reindexing content will remove all existing search items for the content type and re-scan the content to rebuild the search word index. This can take a significant amount of time on large volume content types such as Forums.',
    'search_site'       => "Search {$_CONF['site_name']}",
);

$LANG_SRCH_ADM = array(
    'reindex_title'     => 'Reindex Content',
    'searcher_admin'    => 'Searcher Admin',
    'index_instructions'    => 'This will scan the selected content types and rebuild the searcher index',
    'reindex_button'    => 'Reindex',
    'success'           => 'Success',
    'indexing'          => 'Indexing',
    'index_status'      => 'Indexing Status',
    'retrieve_content_types'   => 'Retrieving Content Types',
    'error_heading'     => 'Errors',
    'no_errors'         => 'No Errors',
    'error_getcontenttypes' => 'Unable to retrieve content types from glFusion',
    'current_progress'  => 'Current Progress',
    'overall_progress'  => 'Overall Progress',
    'remove_content_1'  => 'Removing existing index entries for ',
    'remove_content_2'  => ' - This can take several minutes....',
    'content_type'      => 'Content Type',
    'remove_fail'       => 'Failed to remove existing index entries.',
    'retrieve_content_list' => 'Retrieving content list for ',
    'chk_unchk_all'     => 'Check/Uncheck All',
);

// Localization of the Admin Configuration UI
$LANG_configsections['searcher'] = array(
    'label' => 'Searcher',
    'title' => 'Search Plugin Configuration',
);

$LANG_confignames['searcher'] = array(
    'pi_display_name'   => 'Display Name',
    'min_word_len'      => 'Minimum Word Length to consider',
    'excerpt_len'       => 'Length of excerpt to display',
    'perpage'           => 'Results to show per page',
    'wt_title'          => 'Weighting for results in titles',
    'wt_content'        => 'Weighting for results in content',
    'wt_author'         => 'Weighting for results in author names',
    'max_occurrences'   => 'Maximum occurrences of a term to count',
    'show_author'       => 'Show author name in results?',
    'stemmer'           => 'Select Stemmer (Experimental)',
    'ignore_autotags'   => 'Ignore Auto Tags',
    'max_word_phrase'   => 'Max words in a phrase',
    'replace_stock_search' => 'Replace stock glFusion search?',
);

$LANG_configsubgroups['searcher'] = array(
    'sg_main' => 'Main Settings',
);

$LANG_fs['searcher'] = array(
    'fs_main' => 'Main Settings',
    'fs_weight' => 'Weightings',
);

$LANG_configselects['searcher'] = array(
    0  => array('True' => 1, 'False' => 0 ),
    1  => array('Yes' => 1, 'No' => 0),
    10 => array(' 1' => 1, ' 2' => 2, ' 3' => 3, ' 4' => 4, ' 5' => 5,
                ' 6' => 6, ' 7' => 7, ' 8' => 8, ' 9' => 9, '10' => 10),
    11 => array('No' => 0, 'Yes, no link' => 1, 'Yes, with link' => 2),
);

?>
