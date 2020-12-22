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
  `ts` int(11) unsigned NOT NULL DEFAULT '0',
  `owner_id` int(11) unsigned NOT NULL DEFAULT '0',
  `grp_access` mediumint(8) NOT NULL DEFAULT '2',
  `content` mediumint(9) NOT NULL DEFAULT '0',
  `title` mediumint(9) NOT NULL DEFAULT '0',
  `author` mediumint(9) NOT NULL DEFAULT '0',
  `weight` float unsigned NOT NULL DEFAULT '1',
  KEY `type_item` (`type`, `item_id`),
  KEY `terms_pid` (`term`,`parent_id`),
  KEY `type_pid` (`type`,`parent_id`),
  KEY `parent` (`parent_type`,`parent_id`)
) ENGINE=MyISAM";

$_SQL['searcher_counters'] = "CREATE TABLE `{$_TABLES['searcher_counters']}` (
  `term` varchar(40) NOT NULL,
  `hits` int(11) unsigned NOT NULL DEFAULT '1',
  `results` int(11) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`term`),
  KEY `hits` (`hits`)
) ENGINE=MyISAM";

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
    '0.0.4' => array(
        "CREATE TABLE `{$_TABLES['searcher_counters']}` (
          `term` varchar(40) NOT NULL,
          `hits` int(11) unsigned NOT NULL DEFAULT '1',
          PRIMARY KEY (`term`),
          KEY `hits` (`hits`)
        ) ENGINE=MyISAM",
    ),
    '0.0.5' => array(
        "ALTER TABLE {$_TABLES['searcher_index']}
            ADD ts int(11) unsigned NOT NULL DEFAULT '0' AFTER parent_type",
    ),
    '0.0.6' => array(
        "ALTER TABLE {$_TABLES['searcher_index']}
            ADD grp_access mediumint(8) unsigned not null default '2' AFTER ts",
        "UPDATE {$_TABLES['searcher_index']} SET grp_access = if(perm_anon = 2, 2,
            if (perm_members = 2, 13, group_id))",
        "ALTER TABLE {$_TABLES['searcher_index']}
            DROP owner_id, DROP group_id, DROP perm_owner, DROP perm_group,
            DROP perm_members, DROP perm_anon",
    ),
    '0.0.9' => array(
        "ALTER TABLE {$_TABLES['searcher_counters']} ADD results int(11) unsigned NOT NULL DEFAULT '1'",
    ),
    '0.1.2' => array(
        "ALTER TABLE {$_TABLES['searcher_index']} DROP KEY `terms`",
        "ALTER TABLE {$_TABLES['searcher_index']} ADD KEY `type_item` (`type`, `item_id`)",
    ),
    '1.1.0' => array(
        "ALTER TABLE {$_TABLES['searcher_index']} ADD owner_id int(11) unsigned NOT NULL DEFAULT '0' AFTER `ts`",
    ),
);

?>
