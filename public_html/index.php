<?php
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
$display .= $Form->showForm();

if (isset($_GET['q'])) {
    USES_searcher_class_searcher();
    $query = $_GET['q'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $S = new Searcher($query);
    $S->doSearch($query, $page);
    $display .= $S->Display();
}
$display .= COM_siteFooter();

echo $display;

?>
