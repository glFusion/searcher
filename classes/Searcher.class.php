<?php
/**
 * Perform searches from the index maintained by the Indexer class.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2020 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v0.1.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher;

/**
 * Searcher class.
 * @package searcher
 */
class Searcher extends Common
{
    /** Search results.
     * @var array */
    protected $results = array();

    /** Current results page number.
     * @var integer */
    protected $_page = 1;

    /** Number of days to limit search, 0 = unlimited.
     * @var integer */
    protected $_searchDays = 0;

    /** Item type filter.
     * @var string */
    protected $_type = '';

    /** Display style. Only `inline` currently supported.
     * @var string */
    protected $_style = 'inline';

    /** Array of search keys (content, author).
     * @var array */
    protected $_keys = array();

    /** Sanitized query string from user input.
     * @var string */
    protected $query = '';

    /** Tokenized query string.
     * @var array() */
    protected $tokens = array();

    /** SQL-safe query string for searching.
     * @var string */
    protected $sql_tokens = '';

    /** Whether to consider title in search.
     * @var boolean */
    protected $_search_title   = 0;

    /** Whether to consider content in search.
     * @var boolean */
    protected $_search_content = 0;

    /** Whether to consider author in search.
     * @var boolean */
    protected $_search_author  = 0;


    /**
     * Consrtructer. Call Parent initializer and set the query string.
     *
     * @param   string  $query  Optional query string
     */
    public function __construct($query='')
    {
        parent::__construct();

        if (isset($_GET['type'])) {
            $this->setType($_GET['type']);
        }

        if (isset($_GET['st'])) {
            $this->setDays($_GET['st']);
        }

        if (isset($_GET['page'])) {
            $this->setPage($_GET['page']);
        }

        // Could pass in a query string, but most likely comes from $_GET
        if (!empty($query)) {
            $this->setQuery($query);
        } elseif (isset($_GET['q'])) {
            $this->setQuery($_GET['q']);
        } elseif (isset($_GET['query'])) {
            $this->setQuery($_GET['query']);
        }

        // Copy fields and weights into a local array
        // May be overridden by setKeys()
        foreach(self::$fields as $fld=>$wt) {
            $this->_keys[$fld] = $wt;
        }

        $searchKeys = array();

        // check to see which search keys are enabled
        if ( isset($_GET['author'])) {
            $this->setSearchKey('author',COM_applyFilter($_GET['author']));
            if ( $this->_search_author > 1 ) {
                if ( $this->query == '' || empty($this->query)) {
                    $authorName = COM_getDisplayName($this->_search_author);
                    if ( $authorName != '' ) {
                        $this->setQuery($authorName);
                    }
                }
            }
            $searchKeys[] = 'author';
        }
        if ( isset($_GET['title']) ) {
              $searchKeys[] = 'title';
              $this->setSearchKey('title',1);
        }
        if ( isset($_GET['content'])) {
            $searchKeys[] = 'content';
            $this->setSearchKey('content',1);
        }

        // failsafe - if no search keys checked then enable all
        if ( count($searchKeys) == 0 ) {
            $searchKeys = array('content','title','author');
            $this->setSearchKey('author',0);
            $this->setSearchKey('content',1);
            $this->setSearchKey('title',1);
        }
        $this->setKeys($searchKeys);
    }


    /**
     * Sets the query string and extracts tokens.
     *
     * @param   string  $query  Query string
     * @return  object  $this
     */
    public function setQuery($query)
    {
        $tokens = array();
        $this->query = self::_remove_punctuation($query);
        $this->tokens = self::Tokenize($query, true);
        // longest search terms first, because those are generally more significant
        uksort($this->tokens, array(__CLASS__, '_strlen_sort'));
        foreach ($this->tokens as $token=>$dummy) {
            $tokens[] = DB_escapeString($token);
        }
        if (!empty($tokens)) {
            $this->sql_tokens = "'" . implode("','", $tokens) . "'";
        }
        return $this;
    }


    /**
     * Set the search scope by item type (article, staticpage, etc).
     *
     * @param   string  $type   Type of item
     * @return  object  $this
     */
    public function setType($type)
    {
        switch ($type) {
        case '':
        case 'all':
            $this->_type = '';
            break;
        case 'stories':
            $this->_type = 'article';
            break;
        case 'comments':
            $this->_type = 'comment';
            break;
        default:
            $this->_type = DB_escapeString($type);
        }
        return $this;
    }


    /**
     * Set the number of days to limit search.
     *
     * @param   integer $days   Number of days to limit search or 0 for no limit
     * @return  object  $this
     */
    public function setDays($days)
    {
        $days = (int)$days;
        if ($days < 0) $days = 0;
        $this->_searchDays = $days;
        return $this;
    }


    /**
     * Set the key fields to only consider certain keys.
     *
     * @param   array   $keys   Array of key names
     * @return  object  $this
     */
    public function setKeys($keys)
    {
        // Reset the keys array
        $this->_keys = array();

        // Make sure $keys is an array
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $key) {
            $this->_keys[$key] = self::$fields[$key];
        }
        return $this;
    }


    /**
     * Set the search page number, minimum is "1".
     *
     * @param   integer $page   Page number
     * @return  object  $this
     */
    public function setPage($page = 1)
    {
        $this->_page = $page > 0 ? (int)$page : 1;
        return $this;
    }


    /**
     * Set search keys.
     *
     * @param   string  $item   Search key (content, title, author)
     * @param   integer $value  value to search
     * @return  object  $this
     */
    public function setSearchKey($item, $value)
    {
        switch ( $item ) {
        case 'author' :
            // set _search_author to UID to search
            // UID is only used if query is blank in which case
            // Searcher will populate query with results of COM_getDisplayName()
            $value = (int)$value;
            if ($value < 0) {
                $value = 0;
            }
            $this->_search_author = $value;
            break;
        case 'content' :
            $this->_search_content = 1;
            break;
        case 'title' :
            $this->_search_title = 1;
            break;
        }
        return $this;
    }


    /**
     * Get the "where" and "group by" clauses for sql statements.
     * Where clause is common to totalResults() and doSearch() so it's
     * centralized here.
     *
     * @return  string  SQL where clause for queries
     */
    private function _sql_where()
    {
        if (!empty($this->sql_tokens)) {
            $where = " term in ({$this->sql_tokens}) ";
        } else {
            $where = ' 1=1 ';
        }

        if ($this->_type != '') {
            $where .= " AND type = '{$this->_type}' ";
        }

        if ($this->_search_author > 0) {
            $where .= " AND owner_id = " . (int)$this->_search_author;
        }

        // Don't search comments if internal comments are disabled
        if ( ! self::CommentsEnabled() ) {
            $where .= " AND type <> 'comment' ";
        }

        if ($this->_searchDays > 0) {
            $daysback = time() - ($this->_searchDays * 86400);
            $where .= ' AND ts > ' . (int)$daysback;
        }

        // if only 1 or 2 search keys are checked
        // build approach SQL to limit search to only those keys
        if ( count($this->_keys) != 3 ) {
            $skWhere = '';
            $skLoop = 0;
            foreach ($this->_keys AS $key => $weight) {
                if ( $skLoop > 0 ) $skWhere .= ' OR ';
                $skWhere .= $key . ' > 0 ';
                $skLoop++;
            }
            $where .= ' AND (' . $skWhere .' ) ';
        }

        $where .= $this->_getPermSQL() .
            ' GROUP BY type, si.item_id ';
        return $where;
    }


    /**
     * Perform the search.
     *
     * @param   boolean $api    True to return raw results array (TESTING)
     * @return  array   Array of results (item_id, url, excerpt, etc.)
     */
    public function doSearch($api=false)
    {
        global $_TABLES, $_SRCH_CONF, $_USER;

        $x = strlen($this->query);
        if (
            !self::SearchAllowed() ||
            ($x < self::$min_word_len && count($this->_keys) == 3)
        ) {
            // no query and all keys enabled - return search form only - no search
            return $this->showForm($x);
        }

        // Set the starting record limt. `$this->_page` should never be less than one.
        $start = (max($this->_page, 1) - 1) * $_SRCH_CONF['perpage'];
        foreach ($this->_keys as $fld=>$weight) {
            if ($_SRCH_CONF['max_occurrences'] > 0) {
                $wts[] = '(LEAST(' . $fld . ',' .
                    (int)$_SRCH_CONF['max_occurrences'] .
                    ') * ' . $weight . ' * weight)';
            } else {
                $wts[] = '(' . $fld . ' * ' . $weight . ' * weight)';
            }
        }
        $wts = implode(' + ' , $wts);
        $sql = "SELECT type, MAX(si.item_id) AS item_id,
            SUM($wts) AS relevance
            FROM {$_TABLES['searcher_index']} si
            WHERE " . $this->_sql_where() .
            " ORDER BY relevance DESC, ts DESC
            LIMIT $start, {$_SRCH_CONF['perpage']}";
        $res = DB_query($sql);
        $this->results = array();
        // Set field array for PLG_getItemInfo.
        // Stories have date-created, Pages have date-modified.
        $what = 'id,title,description,author,author_name,date,date-created,date-modified,hits,url';
        while ($A = DB_fetchArray($res, false)) {
            $exc = PLG_getItemInfo($A['type'], $A['item_id'], $what, $_USER['uid']);
            if (!$exc) {
                continue;
            }
            if (!empty($exc['date'])) {
                $date = $exc['date'];
            } elseif (!empty($exc['date-modified'])) {
                $date = $exc['date-modified'];
            } elseif (!empty($exc['date-created'])) {
                $date = $exc['date-created'];
            } else {
                    $date = NULL;
            }
            $excerpt = self::getExcerpt($exc['description']);
            $hits = isset($exc['hits']) ? $exc['hits'] : NULL;

            if ( $exc['author_name'] == '' ) {
                $author = is_numeric($exc['author']) ?
                        COM_getDisplayName($exc['author']) : $exc['author'];
            } else {
                $author = $exc['author_name'];
            }
            $title = $exc['title'];
            $uid = isset($exc['author']) ? $exc['author'] : NULL;
            if (isset($exc['url'])) {
                $urlArray = parse_url($exc['url']);
                $url = $urlArray['scheme'] . '://';
                $url .= ( isset($urlArray['user'] ) ? $urlArray['user'].':' : '');
                $url .= ( isset($urlArray['pass'] ) ? $urlArray['pass'].'@' : '');
                $url .= $urlArray['host'];
                $url .= ( isset($urlArray['port']) ? ':'.$urlArray['port'] : '');
                $url .= ( isset($urlArray['path']) ? $urlArray['path'] : '' );
                $url .= ( isset($urlArray['query']) ? '?' . $urlArray['query'].'&query='.urlencode($this->query) : '?query=' . urlencode($this->query));
                $url .= ( isset($urlArray['fragment']) ? '#'.$urlArray['fragment'] : '');
            } else {
                $url = NULL;
            }

            // Null indicates no result to display, possibly due to permission
            if ($exc !== NULL) {
                $this->results[] = array(
                    'type' => $A['type'],
                    'disp_type' => ucfirst($A['type']),
                    'item_id' => $A['item_id'],
                    'title'  => $title,
                    'relevance' => $A['relevance'],
                    'excerpt' => $excerpt['excerpt'],
                    'author' => $author,
                    'hits' => $hits,
                    'uid' => $uid,
                    'url' => $url,
                    'ts' => $date,
                );
            }
        }
        if ($api) {
            return $this->results;
        } else {
            $this->updateCounter();
            $retval = $this->showForm();
            $retval .= $this->Display();
            return $retval;
        }
    }


    /**
     * Get permissions clause for SQL.
     * Saves in a static var to eliminate repetitive calls
     * Always uses "AND".
     *
     * @return  string  Permissions SQL
     */
    private function _getPermSQL()
    {
        static $perms = NULL;
        if ($perms === NULL) {
            $perms = SEC_buildAccessSQL('AND');
        }
        return $perms;
    }


    /**
     * Get a count of the results on the current page.
     *
     * @return  integer     Count of results
     */
    public function countResults()
    {
        return count($this->results);
    }


    /**
     * Get a count of all possible results for pagination.
     *
     * @return  integer     Total number of results
     */
    public function totalResults()
    {
        global $_TABLES;
        static $count = NULL;

        if ($count === NULL) {
            $sql = "SELECT count(*) AS cnt
                FROM {$_TABLES['searcher_index']} si
                WHERE " . $this->_sql_where();
            //echo $sql;die;
            $res = DB_query($sql);
            $count = (int)DB_numRows($res);
         }
         return $count;
    }


    /**
     * Get the excerpt to display in the search results.
     *
     * @param   string  $content    Entire article/page content
     * @return  string      highlighted excerpt
     */
    public function getExcerpt($content)
    {
        global $_SRCH_CONF;

        $type = '';

        //$type = 'chars';
        $excerpt_length = $_SRCH_CONF['excerpt_len'];
        $best_excerpt_term_hits = -1;
        $excerpt = "";

        $content = preg_replace('/\s+/u', ' ', $content);
        $content = self::stripTags($content);
        $content = " $content";

        $start = false;
        if ('chars' == $type) {
            // TODO - remove this section?
            $excerpt_length *= 5;   // convert number of words to number of chars
            $prev_count = floor($excerpt_length / 2);
            list($excerpt, $best_excerpt_term_hits, $start) = self::_extract_relevant(array_keys($this->tokens), $content, $excerpt_length, $prev_count);
        } else {
            $words = explode(' ', $content);
            $i = 0;
            while ($i < count($words)) {
                if ($i + $excerpt_length > count($words)) {
                    $i = count($words) - $excerpt_length;
                    if ($i < 0) $i = 0;
                }

                $excerpt_slice = array_slice($words, $i, $excerpt_length);
                $excerpt_slice = implode(' ', $excerpt_slice);
                $excerpt_slice = " $excerpt_slice";
                $term_hits = 0;
                $count = self::_count_matches(array_keys($this->tokens), $excerpt_slice);

                if ($count > 0 && $count > $best_excerpt_term_hits) {
                    $best_excerpt_term_hits = $count;
                    $excerpt = $excerpt_slice;
                }
                $i += $excerpt_length;
            }
        }

        if ('' == $excerpt) {
            // No excerpt found? Shouldn't happen, but just get the first X words
            $excerpt = explode(' ', $content, $excerpt_length);
            array_pop($excerpt);
            $excerpt = implode(' ', $excerpt);
            $start = true;
        }
        $excerpt = self::_mb_trim($excerpt);
        return array(
            'excerpt' => $excerpt,
            'hits' => $best_excerpt_term_hits,
            'start' => $start,
        );
    }


    /**
     * Work out which is the most relevant portion to display.
     * This is done by looping over each match and finding the smallest distance between two found
     * strings. The idea being that the closer the terms are the better match the snippet would be.
     * When checking for matches we only change the location if there is a better match.
     * The only exception is where we have only two matches in which case we just take the
     * first as will be equally distant.
     *
     * @param   array   $locations  Array of locations where a result is found.
     * @param   integer $prevcount  Location of previous instance of the result.
     * @return  integer     Starting position within the text for the snippet.
     */
    protected static function _snip_location($locations, $prevcount)
    {
        if (!is_array($locations) || empty($locations)) return 0;

        // If we only have 1 match we dont actually do the for loop so set to the first
        $startpos = $locations[0];
        $loccount = count($locations);
        $smallestdiff = PHP_INT_MAX;

        // If we only have 2 skip as its probably equally relevant
        if (count($locations) > 2) {
            // skip the first as we check 1 behind
            for ($i = 1; $i < $loccount; $i++) {
                if ($i == $loccount-1) { // at the end
                    $diff = $locations[$i] - $locations[$i-1];
                } else {
                    $diff = $locations[$i+1] - $locations[$i];
                }

                if($smallestdiff > $diff) {
                    $smallestdiff = $diff;
                    $startpos = $locations[$i];
                }
            }
        }

        $startpos = $startpos > $prevcount ? $startpos - $prevcount : 0;
        return $startpos;
    }


    /**
     * This code originally written by Ben Boyter
     * http://www.boyter.org/2013/04/building-a-search-result-extract-generator-in-php/
     * Find the locations of each of the words.
     * Nothing exciting here. The array_unique is required
     * unless you decide to make the words unique before passing in.
     *
     * @param   array   $words      Array of words to find
     * @param   string  $fulltext   Full text being searched
     * @return  array       Locations where words were found
     */
    protected static function _extract_locations($words, $fulltext)
    {
        $locations = array();
        foreach ($words as $word) {
            $wordlen = utf8_strlen($word);
            $loc = utf8_stripos($fulltext, $word, 0);
            while($loc !== FALSE) {
                $locations[] = $loc;
                $loc = utf8_stripos($fulltext, $word, $loc + $wordlen);
            }
        }
        $locations = array_unique($locations);
        sort($locations);
        return $locations;
    }



    /**
     * Extract the relevent text for display.
     * 1/6 ratio on prevcount tends to work pretty well and puts the terms
     * in the middle of the extract.
     *
     * @param   array   $words      Words to locate in the text
     * @param   string  $fulltext   Full text
     * @param   integer $rellength  Desired character length of the excerpt
     * @param   integer $prevcount  Desired number of characters before the words
     * @return  array       Array of (excerpt, number of hits, start indicator))
     */
    protected static function _extract_relevant($words, $fulltext, $rellength=300, $prevcount=50)
    {
        $textlength = utf8_strlen($fulltext);

        // If the excerpt 
        if ($textlength <= $rellength) {
            return array($fulltext, 1, 0);
        }

        $locations = self::_extract_locations($words, $fulltext);
        $startpos  = self::_snip_location($locations, $prevcount);

        // if we are going to snip too much...
        if ($textlength-$startpos < $rellength) {
            $startpos = $startpos - ($textlength-$startpos)/2;
        }

        $reltext = utf8_substr($fulltext, $startpos, $rellength);

        // check to ensure we dont snip the last word if thats the match
        if ($startpos + $rellength < $textlength) {
            $reltext = utf8_substr($reltext, 0, utf8_strrpos($reltext, " ")); // remove last word
        }

        $start = $startpos == 0 ? true : false;
        $besthits = count(self::_extract_locations($words, $reltext));

        return array($reltext, $besthits, $start);
    }


    /**
     * Extract quoted phrases from the query.
     *
     * @param   string  $q      Query string
     * @return  array       Array of quoted phrases
     */
    protected static function _extract_phrases($q)
    {
        $pos = utf8_strpos($q, '"');
        $phrases = array();
        while ($pos !== false) {
            $start = $pos;
            $end = utf8_strpos($q, '"', $start + 1);
            if ($end === false) {
                // just one " in the query
                $pos = $end;
                continue;
            }
            $phrase = utf8_substr($q, $start + 1, $end - $start - 1);
            $phrase = utf8_trim($phrase);

            if (!empty($phrase)) {
                $phrases[] = $phrase;
            }
            // move past this phrase and check for the next one
            $pos = $end;
        }
        return $phrases;
    }


    /**
     * Count the number of matches in the text for the search words.
     *
     * @param   array   $words      Array of words to find
     * @param   string  $fulltext   Full text to search
     * @return  integer     Count of matches
     */
    protected static function _count_matches($words, $fulltext)
    {
        $count = 0;
        foreach ($words as $word ) {
            $pattern = '/([\s,\.:;\?!\']'.$word.')/i';
            if (preg_match($pattern, $fulltext, $matches, PREG_OFFSET_CAPTURE)) {
                $count += count($matches) - 1;
            }
            $pattern = '/('.$word.'[\s,\.:;\?!\'])/i';
            if (preg_match($pattern, $fulltext, $matches, PREG_OFFSET_CAPTURE)) {
                $count += count($matches) - 1;
            }
        }
        return $count;
    }


    /**
     * Apply highlighting style for terms found in content.
     *
     * @param   string  $content    Content to scan, e.g. excerpt
     * @param   array   $terms      Terms to highlight
     * @return  string      Highlighted content
     */
    public static function Highlight($content, $terms)
    {
        // Get all the unique search words from the phrases
        $arr = array();
        foreach ($terms as $term=>$count) {
            $words = explode(' ', $term);
            foreach ($words as $word) {
                $arr[$word] = $word;
            }
        }
        $terms = $arr;
        // Sort again because long phrases might have shorter words.
        // Highlighting short terms within words prevents highlighting the
        // whole word.
        uksort($terms, array(__CLASS__, '_strlen_sort'));

        // Duplicate COM_highlightQuery() without the word
        // boundary to highlight partial words matched by the stemmer.
        foreach ($terms as $word) {
            $before = "/(?!(?:[^<]+>|[^>]+<\/a>))\b";
            $after = "/i";
            if ($word <> utf8_encode($word)) {
                if (@preg_match('/^\pL$/u', urldecode('%C3%B1'))) { // Unicode property support
                    $before = "/(?<!\p{L})";
                    $after = "(?!\p{L})/u";
                } else {
                    $before = "/";
                    $after = "/u";
                }
            }
            $HLtext = @preg_replace($before . $word. $after, "<span class=\"highlight\">\\0</span>", $content);
            if ($HLtext !== NULL) {
                $content = $HLtext;
            }
        }
        return $content;
    }


    /**
     * Display the search results.
     *
     * @return  string      Results display
     */
    public function Display()
    {
        global $_CONF, $_SRCH_CONF, $LANG_ADMIN, $LANG09,$LANG05, $LANG_SRCH;

        $retval = '';

        // get all template fields.
        $T = new \Template($_CONF['path'] . 'plugins/searcher/templates/' . $this->_style);
        $T->set_file (array (
            'list'  => 'list.thtml',
            'row'   => 'item_row.thtml',
        ));

        $T->set_var('query', urlencode($this->query));

        if ($this->countResults() == 0) {
            $T->set_var('message', $LANG_ADMIN['no_results']);
            $T->parse('output', 'list');

            // No results to show so quickly print a message and exit
            $retval = '';
            if (!empty($title))
                $retval .= COM_startBlock($title, '', COM_getBlockTemplate('_admin_block', 'header'));
            $retval .= $T->finish($T->get_var('output'));
            if (!empty($title)) {
                $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
            }
            return $retval;
        }

        $T->set_var('show_message', 'display:none;');

        // Run through all the results
        $r = 1;
        foreach ($this->results as $row) {
            $fieldvalue = $row['title'] . '<br />' . $row['excerpt'];
            $dt = new \Date($row['ts'], $_CONF['timezone']);
            $row['excerpt'] = self::removeAutoTags($row['excerpt'], true);
            $T->set_var(array(
                'title' => self::Highlight($row['title'], $this->tokens),
                'excerpt' => self::Highlight($row['excerpt'], $this->tokens),
                'author' => $_SRCH_CONF['show_author'] ? $row['author'] : NULL,
                'uid'   => $row['uid'],
                'hits'  => $row['hits'],
                'item_url' => $row['url'],
                'date'  => $row['ts'] ? $dt->format($_CONF['shortdate']) : NULL,
                'src'   => $row['disp_type'],
                'type'  => $row['type'],
                'link_author' => ($_SRCH_CONF['show_author'] == 2 && $row['uid'] > 1) ? true : false,
            ) );
            $T->parse('item_field', 'field', true);

            // Write row
            $r++;
            $T->set_var('cssid', ($r % 2) + 1);
            $T->parse('item_row', 'row', true);
            $T->clear_var('item_field');
        }

        $num_pages = ceil($this->totalResults() / $_SRCH_CONF['perpage']);
        $base_url = SRCH_URL . '/index.php?query=' . urlencode($this->query);

        if ( $this->_type != "" ) {
            $base_url .= '&amp;type='.urlencode($this->_type);
        }
        if ( $this->_searchDays > 0 ) {
            $base_url .= '&amp;st='.$this->_searchDays;
        }
        if ( $this->_search_author > 0 ) {
            $base_url .= '&amp;author='.$this->_search_author;
        }
        if ( $this->_search_title > 0 ) {
            $base_url .= '&amp;title=x';
        }
        if ( $this->_search_content > 0 ) {
            $base_url .= '&amp;content=x';
        }

        $pagination = COM_printPageNavigation($base_url, $this->_page, $num_pages);
        $T->set_var('google_paging', $pagination);

        if ($this->countResults() > 0) {
            $first = (($this->_page - 1) * $_SRCH_CONF['perpage']) + 1;
            $last = min($first + $_SRCH_CONF['perpage'] - 1, $this->totalResults());
            $T->set_var('showing_results',
                sprintf($LANG_SRCH['showing_results'], $first, $last, $this->totalResults()));
        }

        // Do the actual output
        $T->parse('output', 'list');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Update the search term counter table.
     */
    private function updateCounter()
    {
        global $_TABLES;

        // To count unique queries, only count if on the first page
        if (!isset($_GET['nc']) && $this->_page == 1) {
            $results = $this->totalResults();
            $query = DB_escapeString($this->query);
            $sql = "INSERT INTO {$_TABLES['searcher_counters']}
                    (term, hits, results) VALUES ('$query', 1, $results)
                    ON DUPLICATE KEY UPDATE
                        hits = hits + 1,
                        results = $results";
            DB_query($sql);
        }
    }


    /**
     * Shows search form.
     * If the query_len is >=0 but less than min_word_len, then also show
     * an error message.
     *
     * @param   integer $query_len  Length of current query
     * @return  string  HTML output for form
     */
    public function showForm($query_len = -1)
    {
        global $LANG09, $LANG_SRCH;

        // Verify current user my use the search form
        if (! self::SearchAllowed() ) {
            return self::getAccessDeniedMessage();
        }

        $T = new \Template(SRCH_PI_PATH . '/templates');
        $T->set_file('searchform', 'searchform.thtml');

        $plugintypes = array(
            'all' => $LANG09[4],
            'article' => $LANG09[6],
        );
        if (self::CommentsEnabled()) {
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
            'min_word_len' => self::$min_word_len,
            'show_adv' => isset($_GET['adv']) && $_GET['adv'] == 1 ? 1 : 0,
        ) );

        if ( $this->_search_author ) {
            $T->set_var('search_author_checked',' checked="checked" ');
            $T->set_var('search_author_value', $this->_search_author);
        }
        if ( $this->_search_title ) $T->set_var('search_title_checked',' checked="checked" ');
        if ( $this->_search_content ) $T->set_var('search_content_checked',' checked="checked" ');
        if ( $query_len > -1 && $query_len < self::$min_word_len ) {
            $T->set_var('err_msg', sprintf($LANG_SRCH['query_too_short'], self::$min_word_len));
        }
        $T->parse('output', 'searchform');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Determines if user is allowed to perform a search.
     *
     * glFusion has a number of settings that may prevent
     * the access anonymous users have to the search engine.
     * This performs those checks.
     *
     * @return  boolean     True if search is allowed, otherwise false
     */
    public static function SearchAllowed()
    {
        global $_CONF;

        // The checks aren't expensive functions, but this gets called twice
        // so save the result.
        static $isAllowed = NULL;

        if ($isAllowed === NULL) {
            if (
                COM_isAnonUser() &&
                ( $_CONF['loginrequired'] || $_CONF['searchloginrequired'] )
            ) {
                $isAllowed = false;
            } else {
                $isAllowed = true;
            }
        }
        return $isAllowed;
    }


    /**
     * Shows an error message if search is not allowed.
     * Search access is only disallowed is if the user is anonymous, so show
     * the login form as the "error message".
     *
     * @return  string  HTML output for error message
     */
    public static function getAccessDeniedMessage()
    {
        return SEC_loginRequiredForm();
    }

}

?>
