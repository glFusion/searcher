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

USES_searcher_class_common();

/**
*   Class to display the search form
*   @package searcher
*/
class SearchForm extends Common
{
    var $_topic = '';
    var $_dateStart = null;
    var $_dateEnd = null;
    var $_author = '';
    //var $_keyType = '';
    var $_results = 25;
    var $_names = array();
    var $_url_rewrite = array();
    var $_searchURL = '';
    var $_wordlength;
    var $_charset = 'utf-8';

    /**
    *   Constructor
    *
    *   Sets up private search variables
    */
    public function __construct()
    {
        global $_CONF, $_TABLES;

        /*if (isset($_GET['topic']) ){
            $this->_topic = COM_applyFilter($_GET['topic']);
        } elseif (isset($_POST['topic'])) {
            $this->_topic = COM_applyFilter($_POST['topic']);
        } else {
            $this->_topic = '';
        }
        if (isset($_GET['datestart'])) {
            $this->_dateStart = COM_applyFilter($_GET['datestart']);
        } elseif (isset($_POST['datestart'])) {
            $this->_dateStart = COM_applyFilter($_POST['datestart']);
        } else {
            $this->_dateStart = '';
        }
        if ($this->_validateDate($this->_dateStart) == false) {
            $this->_dateStart = '';
        }
        if (isset ($_GET['dateend'])) {
            $this->_dateEnd = COM_applyFilter($_GET['dateend']);
        } elseif (isset($_POST['dateend'])) {
            $this->_dateEnd = COM_applyFilter ($_POST['dateend']);
        } else {
            $this->_dateEnd = '';
        }
        if ($this->_validateDate($this->_dateEnd) == false) {
            $this->_dateEnd = '';
        }
        if (isset($_GET['st'])) {
            $st = COM_applyFilter($_GET['st'],true);
            $this->_searchDays = $st;
            if ( $st != 0 ) {
                $this->_dateEnd = date('Y-m-d');
                $this->_dateStart = date('Y-m-d', time() - ($st * 24 * 60 * 60));
            }
        }
        if (isset ($_GET['author'])) {
            $this->_author = COM_applyFilter($_GET['author']);
        } else if ( isset($_POST['author']) ) {
            $this->_author = COM_applyFilter($_POST['author']);
        } else {
            $this->_author = '';
        }
        if ( $this->_author != '' ) {
            // In case we got a username instead of uid, convert it.  This should
            // make custom themes for search page easier.
            if (!is_numeric($this->_author) && !preg_match('/^([0-9]+)$/', $this->_author) && $this->_author != '')
                $this->_author = DB_getItem($_TABLES['users'], 'uid', "username='" . DB_escapeString ($this->_author) . "'");

            if ($this->_author < 1)
                $this->_author = '';
        }*/
        if (isset($_GET['type'])) {
            $this->setType($_GET['type']);
        } else if (isset($_POST['type'])) {
            $this->setType($_POST['type']);
        } else {
            $this->setType('all');
        }

        if (isset($_GET['st'])) {
            $this->setDays($_GET['st']);
        }
/*
        if ( isset($_GET['keyType']) ) {
            $this->_keyType = COM_applyFilter($_GET['keyType']);
        } else if ( isset($_POST['keyType']) ) {
            $this->_keyType = COM_applyFilter($_POST['keyType']);
        } else {
            $this->_keyType = $_CONF['search_def_keytype'];
        }

        if ( isset($_GET['results']) ) {
            $this->_results = COM_applyFilter($_GET['results'],true);
        } else if ( isset($_POST['results']) ) {
            $this->_results = COM_applyFilter($_POST['results']);
        } else {
            $this->_results = $_CONF['num_search_results'];
        }
        */

        $this->_charset = COM_getCharset();
    }

    /**
     * Shows an error message to anonymous users
     *
     * This is called when anonymous users attempt to access search
     * functionality that has been locked down by the glFusion admin.
     *
     * @author Tony Bibbs <tony AT geeklog DOT net>
     * @access private
     * @return string HTML output for error message
     *
     */
    function _getAccessDeniedMessage()
    {
        return (SEC_loginRequiredForm());
    }

    /**
    * Determines if user is allowed to perform a search
    *
    * glFusion has a number of settings that may prevent
    * the access anonymous users have to the search engine.
    * This performs those checks
    *
    * @author Tony Bibbs <tony AT geeklog DOT net>
    * @access private
    * @return boolean True if search is allowed, otherwise false
    *
    */
    function _isSearchAllowed()
    {
        global $_USER, $_CONF;

        if ( COM_isAnonUser() ) {
            if ( $_CONF['loginrequired'] == 1 OR $_CONF['searchloginrequired'] > 0 ) {
                return false;
            }
        }
        return true;
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
        if (!$this->_isSearchAllowed()) {
            return $this->_getAccessDeniedMessage();
        }
        /*switch ($this->_keyType) {
        case 'phrase':
        case 'all':
        case 'any':
            break;
        default:
            $this->_keyType = 'all';
            break;
        }*/

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
            'query'         => htmlspecialchars($this->query),
            'dt_sel_' . $this->_searchDays => 'selected="selected"',
            'lang_date_filter' => $LANG09[71],
        ) );
        $T->parse('output', 'searchform');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    private function DEPRECATE_validateDate( $dateString )
    {
        $delim = substr($dateString, 4, 1);
        if (!empty($delim)) {
            $DS = explode($delim, $dateString);
            if ( intval($DS[0]) < 1970 ) {
                return false;
            }
            if ( intval($DS[1]) < 1 || intval($DS[1]) > 12 ) {
                return false;
            }
            if ( intval($DS[2]) < 1 || intval($DS[2]) > 31 ) {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

}

?>
