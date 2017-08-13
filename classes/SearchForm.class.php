<?php
/**
*   Perform searches from the index maintained by the Indexer class
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

/**
*   Class to display the search form
*   @package searcher
*/
class SearchForm extends Common
{

    /**
    *   Constructor
    *
    *   Sets up private search variables
    */
    public function __construct()
    {
        if (isset($_GET['type'])) {
            $this->setType($_GET['type']);
        } else {
            $this->setType('all');
        }

        if (isset($_GET['st'])) {
            $this->setDays($_GET['st']);
        }
    }


    /**
     * Shows search form
     *
     * @author Tony Bibbs <tony AT geeklog DOT net>
     * @access public
     * @return string HTML output for form
     *
     */
    public function showForm()
    {
        global $_CONF, $_TABLES, $_PLUGINS, $LANG09;

        $retval = '';
        $options = '';

        // Verify current user my use the search form
        if (!$this->SearchAllowed()) {
            return $this->getAccessDeniedMessage();
        }

        $T = new \Template(SRCH_PI_PATH . '/templates');
        $T->set_file(array('searchform' => 'searchform.thtml'));
        $plugintypes = array(
            'all' => $LANG09[4],
            'article' => $LANG09[6],
        );
        if (isset($_CONF['comment_engine']) && $_CONF['comment_engine'] == 'internal') {
            $plugintypes['comment'] = $LANG09[7];
        }
        $plugintypes = array_merge($plugintypes, PLG_getSearchTypes());
        $T->set_block('searchform', 'PluginTypes', 'PluginBlock');
        foreach ($plugintypes as $key => $val) {
            $T->set_var(array(
                'pi_name'   => $key,
                'pi_text'   => $val,
                'selected'  => $this->_type == $key ? 'selected="selected"' : '',
            ) );
            $T->parse('PluginBlock', 'PluginTypes', true);
        }

        $T->set_var(array(
            'query' => htmlspecialchars($this->query),
            'dt_sel_' . $this->_searchDays => 'selected="selected"',
            'lang_date_filter' => $LANG09[71],
        ) );
        $T->parse('output', 'searchform');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }

}

?>
