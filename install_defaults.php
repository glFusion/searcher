<?php
/**
*   Configuration Defaults for the Searcher plugin for glFusion.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner
*   @package    searcher
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// This file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Utility plugin configuration data
*   @global array */
global $_SRCH_CONF;
if (!isset($_SRCH_CONF) || empty($_SRCH_CONF)) {
    $_SRCH_CONF = array();
    require_once dirname(__FILE__) . '/searcher.php';
}

/** Utility plugin default configurations
*   @global array */
global $_SRCH_DEFAULTS;
$_SRCH_DEFAULTS = array(
    'pi_display_name' => 'Searcher',
    'min_word_len' => 3,
    'perpage' => 20,
    'excerpt_len' => 50,
    'wt_title' => 1.5,
    'wt_author' => 1.2,
    'wt_content' => 1,
    'max_occurrences' => 5,
    'show_author' => 2,     // Show author name with link
    'stemmer' => '',
    'ignore_autotags' => 0,
    'max_word_phrase' => 3,
    'replace_stock_search' => false,
);

/**
*   Initialize Searcher plugin configuration
*
*   @return boolean             true: success; false: an error occurred
*/
function plugin_initconfig_searcher()
{
    global $_CONF, $_SRCH_CONF, $_SRCH_DEFAULTS;

    $c = config::get_instance();

    if (!$c->group_exists($_SRCH_CONF['pi_name'])) {

        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true,
                $_SRCH_CONF['pi_name']);
        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true,
                $_SRCH_CONF['pi_name']);

        $c->add('pi_display_name', $_SRCH_DEFAULTS['pi_display_name'],
                'text', 0, 0, NULL, 5, true, $_SRCH_CONF['pi_name']);

        $c->add('min_word_len', $_SRCH_DEFAULTS['min_word_len'],
                'text', 0, 0, NULL, 10, true, $_SRCH_CONF['pi_name']);
        $c->add('max_word_phrase', $_SRCH_DEFAULTS['max_word_phrase'],
                'text', 0, 0, NULL, 15, true, $_SRCH_CONF['pi_name']);
        $c->add('perpage', $_SRCH_DEFAULTS['perpage'],
                'text', 0, 0, NULL, 20, true, $_SRCH_CONF['pi_name']);
        $c->add('excerpt_len', $_SRCH_DEFAULTS['excerpt_len'],
                'text', 0, 0, NULL, 30, true, $_SRCH_CONF['pi_name']);
        $c->add('max_occurrences', $_SRCH_DEFAULTS['max_occurrences'],
                'text', 0, 0, NULL, 40, true, $_SRCH_CONF['pi_name']);
        $c->add('show_author', $_SRCH_DEFAULTS['show_author'],
                'select', 0, 0, 11, 50, true, $_SRCH_CONF['pi_name']);
        $c->add('stemmer', $_SRCH_DEFAULTS['stemmer'],
                'select', 0, 0, 0, 60, true, $_SRCH_CONF['pi_name']);
        $c->add('ignore_autotags', $_SRCH_DEFAULTS['ignore_autotags'],
                'select', 0, 0, 1, 70, true, $_SRCH_CONF['pi_name']);
        $c->add('replace_stock_search', $_SRCH_DEFAULTS['replace_stock_search'],
                'select', 0, 0, 1, 70, true, $_SRCH_CONF['pi_name']);

        $c->add('fs_weight', NULL, 'fieldset', 0, 10, NULL, 0, true,
                $_SRCH_CONF['pi_name']);
        $c->add('wt_title', $_SRCH_DEFAULTS['wt_title'],
                'text', 0, 10, NULL, 10, true, $_SRCH_CONF['pi_name']);
        $c->add('wt_content', $_SRCH_DEFAULTS['wt_content'],
                'text', 0, 10, NULL, 20, true, $_SRCH_CONF['pi_name']);
        $c->add('wt_author', $_SRCH_DEFAULTS['wt_author'],
                'text', 0, 10, NULL, 30, true, $_SRCH_CONF['pi_name']);
     }
     return true;
}

?>
