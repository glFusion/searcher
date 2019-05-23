<?php
/**
 * Admin functions for the Searcher plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2019 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v1.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once $_CONF['path'].'plugins/searcher/include/admin.inc.php';

USES_lib_admin();

$display = '';
$pi_title = $_SRCH_CONF['pi_display_name'] . ' ' .
            $LANG32[36] . ' ' . $_SRCH_CONF['pi_version'];

// If user isn't a root user or if the backup feature is disabled, bail.
if (!SEC_inGroup('Root')) {
    COM_accessLog("User {$_USER['username']} tried to access the Searcher admin screen.");
    COM_404();
    exit;
}

/**
 * View the search queries made by guests.
 *
 * @return  string  Admin list of search terms and counts
 */
function SRCH_admin_terms()
{
    global $_CONF, $_SRCH_CONF, $_TABLES, $LANG_ADMIN, $LANG_SRCH, $LANG_LINKS_ADMIN;

    $retval = '';
    $filter = '';

    $token = SEC_createToken();

    $header_arr = array(      # display 'text' and use table field 'field'
        array(
            'text' => $LANG_SRCH['search_terms'],
            'field' => 'term',
            'sort' => true,
        ),
        array(
            'text' => $LANG_SRCH['queries'],
            'field' => 'hits',
            'sort' => true,
            'align' => 'right',
        ),
        array(
            'text' => $LANG_SRCH['results'],
            'field' => 'results',
            'sort' => true,
            'align' => 'right',
        ),
    );

    $retval .= SRCH_adminMenu('counters');

    $defsort_arr = array('field' => 'hits', 'direction' => 'desc');
    $options = array();
    $form_arr = array(
        'top' => '<button class="uk-button uk-button-small uk-button-danger" name="clearcounters">' .
                    $LANG_SRCH['clear_counters'] . '</button>',
    );
    $text_arr = array(
        'has_extras' => true,
        'form_url' => SRCH_ADMIN_URL . '/index.php?counters=x',
    );

    $query_arr = array('table' => 'searcher_counters',
        'sql' => "SELECT term, hits, results FROM {$_TABLES['searcher_counters']}",
        'query_fields' => array('term'),
        'default_filter' => 'WHERE 1=1',
    );
    $retval .= ADMIN_list('searcher', 'SRCH_getListField_counters', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, $filter, $token, $options, $form_arr);

    return $retval;
}


/**
 * Get the value for list fields in admin lists.
 * For the search term list, just returns the field values.
 *
 * @param  string  $fieldname  Name of field
 * @param  mixed   $fieldvalue Field value
 * @param  array   $A          Complete database record
 * @param  array   $icon_arr   Icon array (not used)
 * @param  string  $token      Admin token
 */
function SRCH_getListField_counters($fieldname, $fieldvalue, $A, $icon_arr, $token)
{
    global $_CONF, $_USER, $LANG_ACCESS, $LANG_LINKS_ADMIN, $LANG_ADMIN;

    $retval = '';

    switch($fieldname) {
        case 'term':
            $retval = COM_createlink($fieldvalue,
                SRCH_URL . '/index.php?query=' . urlencode($fieldvalue) . '&nc');
            break;
        default:
            $retval = $fieldvalue;
            break;
    }
    return $retval;
}

//var_dump($_POST);die;
$view = '';
$action = '';
$expected = array(
    // Actions
    'genindex', 'clearcounters', 'dochgweights',
    // Views, no action
    'gen_all', 'counters', 'chgweights',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        break;
    }
}

$content = '';
$message = '';
$view = '';
switch ($action) {
case 'genindex':
    if (!isset($_POST['pi']) || empty($_POST['pi'])) {
        break;
    }
    foreach ($_POST['pi'] as $pi_name=>$checked) {
        $func = 'plugin_IndexAll_' . $pi_name;
        if (function_exists($func)) {
            $count = $func();
            $message .= "<br />$pi_name: Indexed $count Items";
        }
    }
    break;
case 'clearcounters':
    DB_query("TRUNCATE {$_TABLES['searcher_counters']}");
    $view = 'counters';
    break;

case 'dochgweights':
    $fld_sql = array();
    foreach (array('content', 'title', 'author') as $fldname) {
        $newval = (float)$_POST[$fldname];
        $oldval = (float)$_SRCH_CONF['wt_' . $fldname];
        if ($newval != $oldval) {
            $factor = (int)($newval / $oldval);
            $fld_sql[] = "$fldname = $fldname * $factor";
            // Borrowing this variable from functions.inc where it gets
            // set when reading in the config.
            $searcher_config->set('wt_' . $fldname, $newval, $_SRCH_CONF['pi_name']);
            $_SRCH_CONF['wt_' . $fldname] = $newval;
        }
    }
    if (!empty($fld_sql)) {
        $fld_sql = implode(', ', $fld_sql);
        $sql = "UPDATE {$_TABLES['searcher_index']} SET " . $fld_sql;
        //echo $sql;die;
        DB_query($sql);
    }
    break;

default:
    $view = $action;
    break;
}

switch ($view) {
case 'gen_all':
    $content .= SRCH_adminMenu('gen_all');
    $T = new Template(SRCH_PI_PATH . '/templates');
    $T->set_file('admin', 'admin.thtml');
    $T->set_var(array(
        'pi_url'    => SRCH_URL,
        'header'    => $_SRCH_CONF['pi_display_name'],
        'version'   => $_SRCH_CONF['pi_version'],
        'pi_icon'   => plugin_geticon_searcher(),
    ) );
    foreach ($_PLUGINS as $pi_name) {
        if (function_exists('plugin_IndexAll_' . $pi_name)) {
            $T->set_block('admin', 'plugins', 'pRow');
            $T->set_var('pi_name', $pi_name);
            $T->parse('pRow', 'plugins', true);
        }
    }
    $T->parse('output', 'admin');
    $content .= $T->finish($T->get_var('output'));
    break;

case 'chgweights':
    $content .= SRCH_adminMenu('gen_all');
    $T = new Template(SRCH_PI_PATH . '/templates');
    $T->set_file('admin', 'weightings.thtml');
    $T->set_var(array(
        'pi_url'    => SRCH_URL,
        'header'    => $_SRCH_CONF['pi_display_name'],
        'version'   => $_SRCH_CONF['pi_version'],
        'pi_icon'   => plugin_geticon_searcher(),
        'wt_content' => $_SRCH_CONF['wt_content'],
        'wt_author' => $_SRCH_CONF['wt_author'],
        'wt_title'  => $_SRCH_CONF['wt_title'],
        'lang_wt_title' => $LANG_confignames['searcher']['wt_title'],
        'lang_wt_content' => $LANG_confignames['searcher']['wt_content'],
        'lang_wt_author' => $LANG_confignames['searcher']['wt_author'],
    ) );
    $T->parse('output', 'admin');
    $content .= $T->finish($T->get_var('output'));
    break;

case 'counters':
default:
    $content .= SRCH_admin_terms();
    break;
}

$display .= COM_siteHeader('menu', $pi_title);
$display .= $content;
$display .= $message;
$display .= COM_siteFooter();

echo $display;

?>
