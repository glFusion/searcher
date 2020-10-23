<?php
/**
 * Configuration Defaults for the Searcher plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2019 Lee Garner
 * @package     searcher
 * @version     v1.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// This file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Searcher plugin default configurations
 *   @global array */
global $searcherConfigData;
$searcherConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'pi_display_name',
        'default_value' => 'Searcher',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'min_word_len',
        'default_value' => 3,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'max_word_phrase',
        'default_value' => 3,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'perpage',
        'default_value' => 20,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'excerpt_len',
        'default_value' => 20,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 50,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'max_occurrences',
        'default_value' => 5,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 60,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'show_author',
        'default_value' => 2,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 11,
        'sort' => 70,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'stemmer',
        'default_value' => '',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,     // uses helper function
        'sort' => 80,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'ignore_autotags',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 1,
        'sort' => 90,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'replace_stock_search',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 1,
        'sort' => 100,
        'set' => true,
        'group' => 'searcher',
    ),

    // Relevance weights
    array(
        'name' => 'fs_weight',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'wt_title',
        'default_value' => 1.5,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'searcher',
    ),
    array(
        'name' => 'wt_author',
        'default_value' => 1.2,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'searcher',
    ),

    array(
        'name' => 'wt_content',
        'default_value' => 1,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'searcher',
    ),
);


/**
 * Initialize Paypal plugin configuration
 *
 * @param   integer $group_id   Admin Group ID (not used)
 * @return  boolean             True
 */
function plugin_initconfig_searcher($group_id = 0)
{
    global $searcherConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('searcher')) {
        USES_lib_install();
        foreach ($searcherConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    }
    return true;
}


/**
 * Sync the configuration in the DB to the above configs
 */
function plugin_updateconfig_searcher()
{
    global $searcherConfigData;

    USES_lib_install();
    _update_config('searcher', $searcherConfigData);
}

?>
