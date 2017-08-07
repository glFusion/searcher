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
require_once __DIR__ . '/Common.class.php';

/**
*   Searcher class
*   @package searcher
*/
class Searcher extends Common
{
    private $count = NULL;
    private $results = array();
    private $page = 1;
    private $_style = 'inline';
    private $_keys = array();

    /**
    *   Consrtructer. Call Parent initializer and set the query string
    *
    *   @param  string  $query  Optional query string
    */
    public function __construct($query='')
    {
        parent::__construct();
        if (!empty($query)) {
            $this->setQuery($query);
        }
        // Copy fields and weights into a local array
        // May be overridden
        foreach(self::$fields as $fld=>$wt) {
            $this->_keys[$fld] = $wt;
        }
    }


    /**
    *   Set the key fields to only consider certain keys
    *
    *   @param  array   $keys   Array of key names
    */
    public function setKeys($keys)
    {
        $this->_keys = array();
        foreach ($keys as $key) {
            $this->_keys[$key] = self::$fields[$key];
        }
    }


    /**
    *   Get the "where" and "group by" clauses for sql statements.
    *   Where clause is common to totalResults() and doSearch() so it's
    *   centralized here.
    *
    *   @return string      SQL where clause for queries
    */
    private function _sql_where()
    {
        $type_sql = $this->_type ? " AND type = '{$this->_type}' " : '';

        $daysback = time() - ($this->_searchDays * 24 * 60 * 60);
        $type_sql .= $this->_searchDays > 0 ? " AND ts > " . $daysback . " " : "";

        $where = " term in ({$this->sql_tokens}) " . $type_sql .
            $this->_getPermSQL() .
            ' GROUP BY  type, item_id ';
        return $where;
    }


    /**
    *   Perform the search
    *
    *   @param  string  $query  Optional query string if not set previously
    *   @param  integer $page   Page number to retrieve
    *   @return array           Array of results (item_id, url, excerpt, etc.)
    */
    public function doSearch($page = 1)
    {
        global $_TABLES, $_SRCH_CONF, $_USER;

        if (empty($this->tokens)) {
            return array();
        }

        $this->page = $page > 0 ? $page : 1;
        $start = $page < 2 ? 0 : ($page - 1) * $_SRCH_CONF['perpage'];
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
        $sql = "SELECT type, item_id, term, sum($wts) as relevance
            FROM {$_TABLES['searcher_index']}
            WHERE " . $this->_sql_where() .
            " ORDER BY relevance DESC, ts DESC
            LIMIT $start, {$_SRCH_CONF['perpage']}";
        //echo $sql."\n";die;
        $res = DB_query($sql);
        $this->results = array();
        // Set field array for PLG_getItemInfo.
        // Stories have date-created, Pages have date-modified.
        $what = 'id,title,description,author,date,date-created,date-modified,hits,url';
        while ($A = DB_fetchArray($res, false)) {
            $exc = PLG_getItemInfo($A['type'], $A['item_id'], $what, $_USER['uid']);
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
            $author = is_numeric($exc['author']) ?
                    COM_getDisplayName($exc['author']) : $exc['author'];
            $title = $exc['title'];
            $uid = isset($exc['author']) ? $exc['author'] : NULL;
            if (isset($exc['url'])) {
                $url = $exc['url'];
                $sep = strpos($url, '?') ? '&' : '?';
                $url .= $sep . 'query=' . urlencode($this->query);
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
        $this->updateCounter();
        return $this->results;
    }


    /**
    *   Get permissions clause for SQL.
    *   Saves in a static var to eliminate repetitive calls
    *   Always uses "AND".
    *
    *   @return string  Permissions SQL
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
    *   Get a count of the results on the current page
    *
    *   @return integer     Count of results
    */
    public function countResults()
    {
        return count($this->results);
    }


    /**
    *   Get a count of all possible results for pagination
    *
    *   @return integer     Total number of results
    */
    public function totalResults()
    {
        global $_TABLES;

        if ($this->count === NULL) {
            $sql = "SELECT count(*) AS cnt
                FROM {$_TABLES['searcher_index']}
                WHERE " . $this->_sql_where();
            //echo $sql;die;
            $res = DB_query($sql);
            $this->count = (int)DB_numRows($res);
         }
         return $this->count;
    }


    /**
    *   Get the excerpt to display in the search results.
    *
    *   @param  string  $content    Entire article/page content
    *   @return string      highlighted excerpt
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
        $content = strip_tags($content);
        $content = " $content";

        // longest search terms first, because those are generally more significant
        $terms = $this->tokens;     // Copy the token array
        uksort($terms, array(__CLASS__, '_strlen_sort'));

        $start = false;
        if ('chars' == $type) {
            $excerpt_length *= 5;
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
                $count = self::_count_matches(array_keys($terms), $excerpt_slice);

                if ($count > 0 && $count > $best_excerpt_term_hits) {
                    $best_excerpt_term_hits = $count;
                    $excerpt = $excerpt_slice;
                }
                $i += $excerpt_length;
            }
        }

        if ('' == $excerpt) {
            // Just get the first X words
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


    // Work out which is the most relevant portion to display
    // This is done by looping over each match and finding the smallest distance between two found
    // strings. The idea being that the closer the terms are the better match the snippet would be.
    // When checking for matches we only change the location if there is a better match.
    // The only exception is where we have only two matches in which case we just take the
    // first as will be equally distant.
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


    /******
     * This code originally written by Ben Boyter
     * http://www.boyter.org/2013/04/building-a-search-result-extract-generator-in-php/
     */

    // find the locations of each of the words
    // Nothing exciting here. The array_unique is required
    // unless you decide to make the words unique before passing in
    protected static function _extract_locations($words, $fulltext)
    {
        $locations = array();
        foreach ($words as $word) {
            $wordlen = self::_strlen($word);
            $loc = self::_stripos($fulltext, $word, 0);
            while($loc !== FALSE) {
                $locations[] = $loc;
                $loc = self::_stripos($fulltext, $word, $loc + $wordlen);
            }
        }
        $locations = array_unique($locations);
        sort($locations);
        return $locations;
    }



    // 1/6 ratio on prevcount tends to work pretty well and puts the terms
    // in the middle of the extract
    protected static function _extract_relevant($words, $fulltext, $rellength=300, $prevcount=50)
    {
        $textlength = self::_strlen($fulltext);

        if ($textlength <= $rellength) {
            return array($fulltext, 1, 0);
        }

        $locations = self::_extract_locations($words, $fulltext);
        $startpos  = self::_snip_location($locations, $prevcount);

        // if we are going to snip too much...
        if ($textlength-$startpos < $rellength) {
            $startpos = $startpos - ($textlength-$startpos)/2;
        }

        $reltext = call_user_func(self::$substr, $fulltext, $startpos, $rellength);

        // check to ensure we dont snip the last word if thats the match
        if ($startpos + $rellength < $textlength) {
            $reltext = call_user_func(self::$substr, $reltext, 0, call_user_func(self::$strrpos, $reltext, " ")); // remove last word
        }

        $start = false;
        if ($startpos == 0) $start = true;

        $besthits = count(self::_extract_locations($words, $reltext));

        return array($reltext, $besthits, $start);
    }

    protected static function DEPRECATED_extract_phrases($q)
    {
        $pos = call_user_func(self::$strpos, $q, '"');
        $phrases = array();
        while ($pos !== false) {
            $start = $pos;
            $end = call_user_func(self::$strpos, $q, '"', $start + 1);
            if ($end === false) {
                // just one " in the query
                $pos = $end;
                continue;
            }
            $phrase = call_user_func(self::$substr, $q, $start + 1, $end - $start - 1);
            $phrase = trim($phrase);

            if (!empty($phrase)) $phrases[] = $phrase;
            $pos = $end;
        }
        return $phrases;
    }

    protected static function _count_matches($words, $fulltext)
    {
        $count = 0;
        foreach ($words as $word ) {
            //        $word = relevanssi_add_accent_variations($word);

            /*if (get_option('relevanssi_fuzzy') == 'never') {
                $pattern = '/([\s,\.:;\?!\']'.$word.'[\s,\.:;\?!\'])/i';
                if (preg_match($pattern, $fulltext, $matches, PREG_OFFSET_CAPTURE)) {
                    $count += count($matches) - 1;
                }
            }
            else {*/
            $pattern = '/([\s,\.:;\?!\']'.$word.')/i';
            if (preg_match($pattern, $fulltext, $matches, PREG_OFFSET_CAPTURE)) {
                $count += count($matches) - 1;
            }
            $pattern = '/('.$word.'[\s,\.:;\?!\'])/i';
            if (preg_match($pattern, $fulltext, $matches, PREG_OFFSET_CAPTURE)) {
                $count += count($matches) - 1;
            }
            //}
        }
        return $count;
    }


    /*
    *   Apply highlighting style for terms found in content
    *
    *   @param  string  $content    Content to scan, e.g. excerpt
    *   @param  array   $terms      Terms to highlight
    */
    public static function Highlight($content, $terms)
    {
        foreach ($terms as $term=>$count) {
            $term = trim($term);    // Numbers have a leading space
            preg_match_all("/$term+/i", $content, $matches);
            if (is_array($matches[0]) && count($matches[0]) >= 1) {
                foreach ($matches[0] as $match) {
                    $content = str_replace($match, '<span class="highlight">'.$match.'</span>', $content);
                }
            }
        }
        return $content;
    }


    /**
    *   Display the search results
    *
    *   @return string      Results display
    */
    public function Display()
    {
        global $_CONF, $_SRCH_CONF, $LANG_ADMIN, $LANG09,$LANG05;

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
            $row['excerpt'] = self::removeAutoTags($row['excerpt']);
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
                'link_author' => $_SRCH_CONF['show_author'] == 2 ? true : false,
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
        $pagination = COM_printPageNavigation($base_url, $this->page, $num_pages);
        $T->set_var('google_paging', $pagination);

        if ($this->countResults() > 0) {
            $first = (($this->page - 1) * $_SRCH_CONF['perpage']) + 1;
            $last = min($first + $_SRCH_CONF['perpage'] - 1, $this->totalResults());
            $T->set_var(array(
                'first_result' => $first,
                'last_result' => $last,
                'total_results' => $this->totalResults(),
            ) );
        }

        $T->parse('output', 'list');

        // Do the actual output
        //$retval = '<div style="margin-top:5px;margin-bottom:5px;border-bottom:1px solid #ccc;"></div>';
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
    *   Update the search term counter table.
    */
    private function updateCounter()
    {
        global $_TABLES;

        if (!isset($_GET['nc'])) {
            $query = DB_escapeString($this->query);
            $sql = "INSERT INTO {$_TABLES['searcher_counters']}
                    (term, hits) VALUES ('$query', 1)
                    ON DUPLICATE KEY UPDATE hits = hits + 1";
            DB_query($sql);
        }
    }

}

?>
