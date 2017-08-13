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
require_once '../lib-common.php';

$S = new Searcher\Searcher();

if ($S->SearchAllowed()) {
    $S->doSearch();
    $results = $S->Display();
} else {
    $results='';
}

$display = COM_siteHeader('menu', $LANG09[11]);
$display .= $S->showForm();
$display .= $results;
$display .= COM_siteFooter();

echo $display;

?>
