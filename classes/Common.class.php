<?php
/**
 * Common functions and variables used by both Indexer and Searcher.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2020 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v1.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher;


/**
 * Common elements for the Searcher plugin.
 * @package searcher
 */
class Common
{
    /** Minimum word length to be considered.
     * @var integer */
    protected static $min_word_len = 3; // default

    /** Array of ignored words.
     * @var array */
    public static $stopwords = array();

    /** Array of fields and assigned weights from the plugin configuration.
     * @var array */
    protected static $fields = array();

    /** Array of autotag module names to keep.
     * When autotags are removed, these will not be affected because they
     * may provide useful search assistance.
     * Metatags and the Tag Cloud plugin are good candidates.
     * @var array */
    protected static $keepAutotags = array('meta', 'tag');


    /**
     * Initialize the Searcher or Indexer class.
     */
    public function __construct()
    {
        self::Init();
    }


    /**
    * Initialize static variables.
    */
    protected static function Init()
    {
        global $_SRCH_CONF;

        self::getStopwords();
        self::$min_word_len = $_SRCH_CONF['min_word_len'];

        // set supported fields
        self::$fields = array(
            'content'   => $_SRCH_CONF['wt_content'],
            'title'     => $_SRCH_CONF['wt_title'],
            'author'    => $_SRCH_CONF['wt_author'],
        );
    }


    /**
     * Trim characters, allowing for multibyte.
     *
     * @param   string  $string     String to modify
     * @return  string      Modified string
     */
    protected static function _mb_trim($string)
    {
        $string = str_replace(chr(194) . chr(160), '', $string);
        $string = preg_replace( "/(^\s+)|(\s+$)/us", '', $string );
        return $string;
    }


    /**
     * Remove puncuation and other special characters from strings.
     *
     * @param   string  $str    String to modify, e.g. content page
     * @return  string      Modified version
     */
    protected static function _remove_punctuation($str)
    {
        if (!is_string($str)) return '';
        $str = strip_tags($str);
        $str = html_entity_decode($str);
        $str = preg_replace("/[^\p{L}\p{N}]/", ' ', $str);
        $str = preg_replace('/\s\s+/', ' ', $str);
        return trim($str);
    }


    /**
     * Callback function to sort strings by length.
     *
     * @param   string  $a  First string
     * @param   string  $b  Second string
     * @return  integer     Length difference (b - a)
     */
    protected static function _strlen_sort($a, $b)
    {
        return utf8_strlen($b) - utf8_strlen($a);
    }


    /**
     * Split a string into individual words.
     * Optionally skip any "stop words"
     *
     * @param   string  $str        Base string to split
     * @param   boolean $query      True if querying, False if indexing
     * @return  array       Array of tokens
     */
    public static function Tokenize($str, $query = false)
    {
        global $_SRCH_CONF;

        $tokens = array();
        if (is_array($str)) {
            foreach ($str as $part) {
                $part = self::removeCensored($part);
                $tokens = array_merge($tokens, self::Tokenize($part));
            }
        }
        if (is_array($str)) {
            return $tokens;
        }

        if (function_exists('mb_internal_encoding'))
            mb_internal_encoding('UTF-8');

        $str = self::_remove_punctuation($str);

        $str = function_exists('mb_strtolower') ?
                mb_strtolower($str) : strtolower($str);

        // Get all the words from the content string. Check against stopwords
        // and minimum word length, if passed then add to the "terms" array.
        $terms = preg_split('/\s+/', $str);

        // Step 1: Get all the terms that aren't excluded by length or
        // stopword status
        $tmp = array();
        foreach ($terms as $term) {
            if (
                in_array($term, self::$stopwords) ||
                utf8_strlen($term) < self::$min_word_len
            ) {
                continue;
            }
            $tmp[] = $term;
        }
        $terms = $tmp;
        $total_terms = count($terms);
        if (!$query) {  // if a query string, save $tmp for later
            unset($tmp);
        }

        // Step 2: Stem the terms, if used. Leave duplicates
        if (!empty($_SRCH_CONF['stemmer'])) {
            $S = Stemmer::getInstance($_SRCH_CONF['stemmer']);
            if ($S !== NULL) {
                for ($i = 0; $i < $total_terms; $i++) {
                    $terms[$i] = $S->stem($terms[$i]);
                }
                // Remove censored words
                $terms = self::removeCensored($terms);

                // If this is a query string, add the original non-stemmed
                // words into the terms array. This is mostly for highlighting.
                if ($query) {
                    foreach ($tmp as $idx=>$word) {
                        if (isset($terms[$idx]) && !in_array($word, $terms)) {
                            $terms[] = $word;
                        }
                    }
                    // Update counter for original terms added back in
                    $total_terms = count($terms);
                }
            }
        }

        // Step 3: Create token array, removes duplicates
        $terms = array_values($terms);
        $total_terms = count($terms);   // reset count
        $tokens = array();
        for ($i = 0; $i < $total_terms; $i++) {
            // Set the term alone in the token array
            $t = $terms[$i];
            if (isset($tokens[$t])) {
                $tokens[$t]['count']++;
            } else {
                $tokens[$t] = array(
                    'count' => 1,
                    'weight' => 1
                );
            }
            // Get the phrases into the token array. Add more weight to
            // longer phrases
            for ($j = 1; $j < $_SRCH_CONF['max_word_phrase']; $j++) {
                if (isset($terms[$i+$j])) {
                    // If not reaching the end of $terms, concatenate
                    // the next term(s)
                    $t .= ' ' . $terms[$i+$j];
                    if (isset($tokens[$t])) {
                        $tokens[$t]['count']++;
                    } else {
                        $tokens[$t] = array(
                            'count' => 1,
                            'weight' => $j + $_SRCH_CONF['phraseweight'],
                        );
                    }
                }
            }
        }

        return $tokens;
    }


    /**
     * Remove autotags before indexing (optional) and before showing results.
     * This option is to prevent search hits on hidden fields that don't
     * actually appear in the content.
     * Some tags can be kept if they provide useful information for searching.
     *
     * @param   string  $content    Content to examine
     * @return  string      Content withoug autotags
     */
    protected static function removeAutoTags($content)
    {
        static $autolinkModules = NULL;
        static $tags = array();

        // Just return content if there are no autotags
        if (strpos($content, '[') === false) {
            return $content;
        }

        $result = $content;
        if ($autolinkModules === NULL) {
            $autolinkModules = PLG_collectTags();
            foreach ($autolinkModules as $moduletag => $module) {
                if (!in_array($moduletag, self::$keepAutotags)) {
                    $tags[] = $moduletag;
                }
            }
            $tags = implode('|', $tags);
        }
        if (!empty($tags)) {
            $result = preg_filter("/\[(($tags):.[^\]]*\])/i", '', $content);
            if ($result === NULL) {
                // Just means no match found
                $result = $content;
            }
        }
        return $result;
    }


    /**
     * Get the list of censored words and pass through teh stemmer.
     *
     * @param   string  $content    Original content
     * @return  string      Sanitized content
     */
    protected static function getCensorList()
    {
        global $_CONF, $_SRCH_CONF;
        static $censorlist = NULL;

        if ($censorlist === NULL) {
            $censorlist = $_CONF['censorlist'];
            if (!empty($_SRCH_CONF['stemmer'])) {
                $S = Stemmer::getInstance($_SRCH_CONF['stemmer']);
                if ($S->isValid()) {
                    foreach ($censorlist as $idx=>$word) {
                        $censorlist[$idx] = $S->stem($word);
                    }
                    $censorlist = array_unique($censorlist);
                }
            }
        }
        return $censorlist;
    }


    /**
     * Remove censored words from the array of terms.
     *
     * @param   array   $terms  Array of terms
     * @return  array       Array of terms with censored words removed
     */
    protected static function removeCensored($terms)
    {
        if (is_array($terms)) {
            return array_diff($terms, self::getCensorList());
        } else {
            return in_array($terms, self::getCensorList()) ? '' : $terms;
        }
    }


    /**
     * Check if the internal comment engine is used.
     * Searcher doesn't index or search external comments on disqus, etc. so
     * comments can be considered "disabled" if anything but the internal
     * engine is used.
     *
     * @return  boolean     True if internal comments are enabled, False if not
     */
    public static function CommentsEnabled()
    {
        global $_CONF;

        return (isset($_CONF['comment_engine']) && $_CONF['comment_engine'] == 'internal');
    }


    /**
     * Load the stopwords into the static array variable.
     * Only English is currrently supported, but additional stopwords can be
     * added by creating a "english.php" file in the "stopwords/custom" subdirectory
     * and adding strings in any language.
     *
     * @return  void    No return, sets the static $stopwords variable
     */
    private static function getStopwords()
    {
        self::$stopwords = array();
        $langfile = 'english.php';
        $langpath = SRCH_PI_PATH . '/stopwords';
        if (is_file("$langpath/$langfile")) {
            require_once "$langpath/$langfile";
            self::$stopwords = $stopwords;
        }

        // Include any custom language file, if found
        if (is_file("$langpath/custom/$langfile")) {
            $stopwords = array();
            include_once "$langpath/custom/$langfile";
            self::$stopwords = array_merge(self::$stopwords, $stopwords);
        }
    }

}

?>
