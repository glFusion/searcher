<?php
/**
*   Table definitions for the lgLib plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner <lee@leegarner.com>
*   @package    lglib
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** @global array $_TABLES */
global $_TABLES, $_SQL, $_UPGRADE_SQL;

$_SQL['searcher_index'] = "CREATE TABLE `{$_TABLES['searcher_index']}` (
  `item_id` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT '',
  `term` varchar(50) NOT NULL DEFAULT '',
  `parent_id` varchar(128) NOT NULL DEFAULT '',
  `parent_type` varchar(50) NOT NULL DEFAULT '',
  `content` mediumint(9) NOT NULL DEFAULT '0',
  `title` mediumint(9) NOT NULL DEFAULT '0',
  `author` mediumint(9) NOT NULL DEFAULT '0',
  `owner_id` mediumint(8) NOT NULL DEFAULT '1',
  `group_id` mediumint(8) NOT NULL DEFAULT '1',
  `perm_owner` tinyint(1) unsigned NOT NULL DEFAULT '3',
  `perm_group` tinyint(1) unsigned NOT NULL DEFAULT '3',
  `perm_members` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `perm_anon` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `weight` float unsigned NOT NULL DEFAULT '1',
  KEY `terms` (`term`),
  KEY `terms_pid` (`term`,`parent_id`),
  KEY `type_pid` (`type`,`parent_id`),
  KEY `parent` (`parent_type`,`parent_id`)
)";

$_UPGRADE_SQL = array(
    '0.0.2' => array(
        "ALTER TABLE {$_TABLES['searcher_index']}
            DROP KEY `itemterm`",
        "ADD `weight` float unsigned not null default 1",
        "UPDATE {$_TABLES['conf_values']}
            SET value = 'text'
            WHERE group_name = '{$_SRCH_CONF['pi_name']}'
            AND name in ('wt_title', 'wt_content', 'wt_author')",
    ),
);

?>
