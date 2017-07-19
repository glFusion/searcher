<?php
/**
*   Default English Language file for the Searcher plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner
*   @package    searcher
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/**
*   The plugin's lang array
*   @global array $LANG_SRCH
*/
$LANG_SRCH = array(
    'list_backups' => 'List Backups',
    'instr_db_bkup_config' => 'Only database tables which exist and are actually used by glFusion are displayed.  To remove tables from the backup, move them into the right pane.',
    'system_message'    => 'System Message',
    'nameparser_suffixes' => array(
        'I', 'II', 'III', 'IV', 'V',
        'Senior', 'Junior', 'Jr', 'Sr',
        'PhD', 'APR', 'RPh', 'PE', 'MD', 'MA', 'DMD', 'CME', 'CPA',
    ),
    // compound elements in names like "Norman Van De Kamp"
    'nameparser_compound' => array(
        'vere', 'von', 'van', 'de', 'del', 'della', 'di', 'da',
        'pietro', 'vanden', 'du', 'st.', 'st', 'la', 'ter',
    ),
    // small words not to be converted by SRCH_titleCase()
    'smallwords' => array(
        'of', 'a', 'the', 'and', 'an', 'or', 'nor', 'but', 'is', 'if', 'then',
        'else', 'when', 'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out',
        'over', 'to', 'into', 'with',
    ),
    'menu_label' => 'Searcher',
);

// Localization of the Admin Configuration UI
$LANG_configsections['searcher'] = array(
    'label' => 'Searcher',
    'title' => 'Search Plugin Configuration',
);

$LANG_confignames['searcher'] = array(
    'min_word_len' => 'Minimum Word Length to consider',
    'excerpt_len' => 'Length of excerpt to display',
    'perpage'   => 'Results to show per page',
    'wt_title' => 'Weighting for results in titles',
    'wt_content' => 'Weighting for results in content',
    'wt_author' => 'Weighting for results in author names',
);

$LANG_configsubgroups['searcher'] = array(
    'sg_main' => 'Main Settings',
);

$LANG_fs['searcher'] = array(
    'fs_main' => 'Main Settings',
    'fs_weight' => 'Weightings',
);

$LANG_configselects['searcher'] = array(
    10 => array(' 1' => 1, ' 2' => 2, ' 3' => 3, ' 4' => 4, ' 5' => 5,
                ' 6' => 6, ' 7' => 7, ' 8' => 8, ' 9' => 9, '10' => 10),
);

?>
