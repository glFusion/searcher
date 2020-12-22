<?php
/**
 * Global configuration items for the Searcher plugin.
 * These are either static items, such as the plugin name and table
 * definitions, or are items that don't lend themselves well to the
 * glFusion configuration system, such as allowed file types.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2020 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_table_prefix, $_TABLES;
global $_SRCH_CONF;

$_SRCH_CONF['pi_name']            = 'searcher';
$_SRCH_CONF['pi_display_name']    = 'Searcher';
$_SRCH_CONF['pi_version']         = '1.1.0';
$_SRCH_CONF['gl_version']         = '1.7.8';
$_SRCH_CONF['pi_url']             = 'http://www.glfusion.org';

$_table_prefix = $_DB_table_prefix . 'searcher_';

$_TABLES['searcher_index']      = $_table_prefix . 'index';
$_TABLES['searcher_counters']   = $_table_prefix . 'counters';

// Semi-static globals
// Additional Weight to apply to additional words in phrases.
// Single words always have a multiplier of "1".
$_SRCH_CONF['phraseweight'] = 1;

?>
