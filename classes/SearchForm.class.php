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
        if (isset($_GET['q'])) {
            $this->_query = strip_tags($_GET['q']);
        } else if (isset($_POST['q'])) {
            $this->_query = strip_tags($_POST['q']);
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
            /*'search_intro'  => $LANG09[19],
            'lang_keywords' => $LANG09[2],
            'lang_date'     => $LANG09[20],
            'lang_to'       => $LANG09[21],
            'date_format'   => $LANG09[22],
            'lang_topic'    => $LANG09[3],
            'lang_all'      => $LANG09[4],
            'topic_option_list' => COM_topicList ('tid,topic', $this->_topic),
            'lang_type'     => $LANG09[5],
            'lang_results'  => $LANG09[59],
            'lang_per_page' => $LANG09[60],
            'lang_exact_phrase' => $LANG09[43],
            'lang_all_words'    => $LANG09[44],
            'lang_any_word'     => $LANG09[45],*/
            'query'         => htmlspecialchars($this->_query),
            /*'datestart'     => $this->_dateStart,
            'dateend'       => $this->_dateEnd,
            'key_' . $this->_keyType . '_selected' => 'selected="selected"',*/
        ) );
/*
        $options = '';
        if ( isset($_CONF['comment_engine']) && $_CONF['comment_engine'] != 'internal') {
            $plugintypes = array('all' => $LANG09[4], 'stories' => $LANG09[6]);
        } else {
            $plugintypes = array('all' => $LANG09[4], 'stories' => $LANG09[6], 'comments' => $LANG09[7]);
        }
        $plugintypes = array_merge($plugintypes, PLG_getSearchTypes());

        foreach ($plugintypes as $key => $val) {
            $options .= "<option value=\"$key\"";
            if ($this->_type == $key)
                $options .= ' selected="selected"';
            $options .= ">$val</option>".LB;
        }
        $plugin_types_option = $options;
        $T->set_var('plugin_types', $options);

        if ($_CONF['contributedbyline'] == 1) {
            $T->set_var('lang_authors', $LANG09[8]);
            $searchusers = array();
            if ( $_CONF['comment_engine'] == 'internal' && isset($_TABLES['comments'])) {
                $result = DB_query("SELECT DISTINCT uid FROM {$_TABLES['comments']}");
                while ($A = DB_fetchArray($result)) {
                    $searchusers[$A['uid']] = $A['uid'];
                }
            }
            if ( isset($_TABLES['stories'])) {
                $result = DB_query("SELECT DISTINCT uid FROM {$_TABLES['stories']} WHERE (date <= NOW()) AND (draft_flag = 0)");
                while ($A = DB_fetchArray($result)) {
                    $searchusers[$A['uid']] = $A['uid'];
                }
            }

            if (in_array('forum', $_PLUGINS) && isset($_TABLES['ff_topic'] ) ) {
                $result = DB_query("SELECT DISTINCT uid FROM {$_TABLES['ff_topic']}");
                while ( $A = DB_fetchArray($result)) {
                    $searchusers[$A['uid']] = $A['uid'];
                }
            }

            $inlist = implode(',', $searchusers);

            if (!empty ($inlist)) {
                $sql = "SELECT uid,username,fullname FROM {$_TABLES['users']} WHERE uid IN ($inlist)";
                if (isset ($_CONF['show_fullname']) && ($_CONF['show_fullname'] == 1)) {
*/
                    /* Caveat: This will group all users with an emtpy fullname
                     *         together, so it's not exactly sorted by their
                     *         full name ...
                     */
/*                    $sql .= ' ORDER BY fullname,username';
                } else {
                    $sql .= ' ORDER BY username';
                }
                $result = DB_query ($sql);
                $options = '';
                $options .= '<option value="all">'.$LANG09[4].'</option>'.LB;
                while ($A = DB_fetchArray($result)) {
                    $options .= '<option value="' . $A['uid'] . '"';
                    if ($A['uid'] == $this->_author) {
                        $options .= ' selected="selected"';
                    }
                    $options .= '>' . htmlspecialchars(COM_getDisplayName($A['uid'], $A['username'], $A['fullname'])) . '</option>';
                }
                $T->set_var('author_option_list', $options);
                $T->parse('author_form_element', 'authors', true);
            } else {
                $T->set_var('author_form_element', '<input type="hidden" name="author" value="0"' . XHTML . '>');
            }
        } else {
            $T->set_var ('author_form_element',
                    '<input type="hidden" name="author" value="0"' . XHTML . '>');
        }

        $searchTimeOptions = array(
            '0' => $LANG09[4],
            '1' => $LANG09[75],
            '7' => $LANG09[76],
            '14' => $LANG09[77],
            '30' => $LANG09[78],
            '90' => $LANG09[79],
            '180' => $LANG09[80],
            '365' => $LANG09[81]);

        // search time frame
        $options = '';
        foreach ( $searchTimeOptions AS $days => $prompt ) {
            $options .= '<option value="'.$days.'"';
            if ( $this->_searchDays == $days ) {
                $options .= ' selected="selected"';
            }
            $options .= '>'.$prompt.'</option>'.LB;
        }
        $date_option = $options;
        $T->set_var('search_time',$options);

        // Results per page
        $options = '';
        $limits = explode(',', $_CONF['search_limits']);
        foreach ($limits as $limit) {
            $options .= "<option value=\"$limit\"";
            if ($this->_results == $limit) {
                $options .= ' selected="selected"';
            }
            $options .= ">$limit</option>" . LB;
        }
        $search_limit_option = $options;
        $T->set_var('search_limits', $options);
*/
//        $T->set_var('lang_search', $LANG09[10]);
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
