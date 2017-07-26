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
if (isset($_GET['query'])) {
    USES_searcher_class_searcher();
    $query = $_GET['query'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $S = new Searcher($query);
    if (isset($_GET['type'])) {
        $S->setType($_GET['type']);
    } elseif (isset($_POST['type'])) {
        $S->setType($_POST['type']);
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
