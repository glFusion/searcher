<?php
/**
*   Front page for the Searcher plugin
*   Displays the search form and, if a query string is provided, shows the
*   search results below.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    searcher
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Searcher;
require_once '../lib-common.php';
USES_searcher_class_searchform();

$display = COM_siteHeader('menu', $LANG09[11]);
/*if (isset ($_GET['mode']) && ($_GET['mode'] == 'search')) {
    //$display .= $searchObj->doSearch();
} else {
    $display = COM_siteHeader ('menu', $LANG09[1]);
    //$display .= $searchObj->showForm();
}*/

$Form = new SearchForm();
if (isset($_GET['query']) && $Form->SearchAllowed()) {
    USES_searcher_class_searcher();
    $query = $_GET['query'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $S = new Searcher($query);
    if (isset($_GET['type'])) {
        $S->setType($_GET['type']);
    }
    if ( isset($_GET['st']) ) {
        $S->setDays($st);
    }
    $S->doSearch($page);
    $results = $S->Display();
    $Form->setQuery($query);
} else {
    $results = '';
}

$display .= $Form->showForm();
$display .= $results;
$display .= COM_siteFooter();

echo $display;

?>
