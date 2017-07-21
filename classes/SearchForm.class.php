<?php
namespace Searcher;

USES_searcher_class_common();

/**
*   glFusion Search Class
*/
class SearchForm extends Common
{
    var $_query = '';
    var $_topic = '';
    var $_dateStart = null;
    var $_dateEnd = null;
    var $_searchDays = 0;
    var $_author = '';
    var $_type = '';
    var $_keyType = '';
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

        // Set search criteria
        if (isset($_GET['query'])) {
            $this->_query = strip_tags($_GET['query']);
        } else if (isset($_POST['query'])) {
            $this->_query = strip_tags($_POST['query']);
        } else {
            $this->_query = '';
        }
        $this->_query = self::_remove_punctuation($this->_query);

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
        }
        if ( isset($_GET['type']) ) {
            $this->_type = COM_applyFilter($_GET['type']);
        } else if ( isset($_POST['type']) ) {
            $this->_type = COM_applyFilter($_POST['type']);
        } else {
            $this->_type = 'all';
        }
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
    * Determines if user is allowed to use the search form
    *
    * glFusion has a number of settings that may prevent
    * the access anonymous users have to the search engine.
    * This performs those checks
    *
    * @author Dirk Haun <Dirk AT haun-online DOT de>
    * @access private
    * @return boolean True if form usage is allowed, otherwise false
    *
    */
    function _isFormAllowed ()
    {
        global $_CONF, $_USER;

        if ( COM_isAnonUser() AND (($_CONF['loginrequired'] == 1) OR ($_CONF['searchloginrequired'] >= 1))) {
            return false;
        }

        return true;
    }

    /**
     * Shows search form
     *
     * Shows advanced search page
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

        // Verify current user my use the search form
        if (!$this->_isFormAllowed()) {
            return $this->_getAccessDeniedMessage();
        }

        switch ($this->_keyType) {
        case 'phrase':
        case 'all':
        case 'any':
            break;
        default:
            $this->_keyType = 'all';
            break;
        }

        $T = new \Template(SRCH_PI_PATH . '/templates');
        $T->set_file(array('searchform' => 'searchform.thtml'));
        $T->set_var(array(
            'query'         => htmlspecialchars($this->_query),
        ) );
        $T->parse('output', 'searchform');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    private function _validateDate( $dateString )
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
