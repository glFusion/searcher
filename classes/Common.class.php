<?php
/**
*   Common functions and variables used by both Indexer and Searcher
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
*   Common elements for the Searcher plugin
*   @package searcher
*/
class Common
{
    protected static $strpos;
    protected static $substr;
    protected static $strrpos;
    protected static $min_word_len = 3; // default
    protected static $stopwords = NULL;

    /**
    *   Initialize static variables
    */
    protected static function Init()
    {
        global $_SRCH_CONF;

        include_once __DIR__ . '/../stopwords/english.php';
        self::$stopwords = $stopwords;

        self::$strpos = function_exists('mb_strpos') ? 'mb_strpos' : 'strpos';
        self::$substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        self::$strrpos = function_exists('mb_strrpos') ? 'mb_strrpos' : 'strrpos';
        self::$min_word_len = $_SRCH_CONF['min_word_len'];
    }


    /**
    *   Trim characters, allowing for multibyte
    *
    *   @param  string  $string     String to modify
    *   @return string      Modified string
    */
    protected static function _mb_trim($string)
    {
        $string = str_replace(chr(194) . chr(160), '', $string);
        $string = preg_replace( "/(^\s+)|(\s+$)/us", '', $string );
        return $string;
    }


    /**
    *   Remove puncuation and other special characters from strings
    *
    *   @param  string  $str    String to modify, e.g. content page
    *   @return string      Modified version
    */
    protected static function _remove_punctuation($str)
    {
        if (!is_string($str)) return '';
        $str = strip_tags($str);
        $repl_nospace = array(          // Replace with nothing
            '.', '…', '€', '&shy;', "\r",
        );

        $repl_space = array(            // Replace these with empty space
            chr(194) . chr(160),
            "'", '&nbsp;', '&#8217;', '"',
            "\n", "\t", '(', ')', '{', '}', '%', '$', '#', '[', ']', '+', '=',
            '_', '-', '"', '`', ',', '<', '>', '=', ':', '?', ';', '&', '/', '\\',
        );

        //$str = preg_replace ('/<[^>]*>/', ' ', $str);
        $str = preg_replace('/\s\s+/', ' ', $str);
        $str = str_replace($repl_nospace, '', $str);
        $str = str_replace($repl_space, ' ', $str);
        preg_replace("/[^[:alnum:][:space:]]/u", '', $str);
        return trim($str);
    }


    /**
    *   Determine the length of a string.
    *   Uses mb_strlen() if available, falls back to strlen()
    *
    *   @param  string  $s      String to check
    *   @return integer         Length of string
    */
    protected static function _strlen($s)
    {
        return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
    }


    /**
    *   Callback function to sort strings by length
    *
    *   @param  string  $a  First string
    *   @param  string  $b  Second string
    *   @return integer     Length difference (b - a)
    */
    protected static function _strlen_sort($a, $b)
    {
        return self::_strlen($b) - self::_strlen($a);
    }


    /* Helper function that does mb_stripos, falls back to mb_strpos and mb_strtoupper
     * if that cannot be found, and falls back to just strpos if even that is not possible.
     */
    protected static function _stripos($content, $term, $offset = 0)
    {
        if ($offset > _strlen($content)) return false;

        if (function_exists('mb_stripos')) {
            $pos = ("" == $content) ? false : mb_stripos($content, $term, $offset);
        } else if (function_exists('mb_strpos') && function_exists('mb_strtoupper') && function_exists('mb_substr')) {
            $pos = mb_strpos(mb_strtoupper($content), mb_strtoupper($term), $offset);
        } else {
            $pos = strpos(strtoupper($content), strtoupper($term), $offset);
        }
        return $pos;
    }


    /**
    *   Split a string into individual words.
    *   Optionally skip any "stop words"
    *
    *   @param  string  $str        Base string to split
    *   @param  boolean $skip_stop  True to skip stop words, False to include
    *   @return array       Array of tokens
    */
    protected static function Tokenize($str, $skip_stop = true)
    {
        //function relevanssi_tokenize($str, $remove_stops = true, $min_word_length = -1) {
        $tokens = array();
        if (is_array($str)) {
            foreach ($str as $part) {
                $tokens = array_merge($tokens, self::Tokenize($part));
            }
        }
        if (is_array($str)) return $tokens;

        if (function_exists('mb_internal_encoding'))
            mb_internal_encoding('UTF-8');

        $str = self::_remove_punctuation($str);

        $str = function_exists('mb_strtolower') ?
                mb_strtolower($str) : strtolower($str);

        $t = strtok($str, "\n\t ");
        while ($t !== false) {
            $t = strval($t);
            $accept = true;
            //if (relevanssi_strlen($t) < self::$min_word_len) {
            if (strlen($t) < self::$min_word_len) {
                $t = strtok("\n\t  ");
                continue;
            }
            /*if ($remove_stops == false) {
                $accept = true;
            } else {*/
                //if (count($stopword_list) > 0) {    //added by OdditY -> got warning when stopwords table was empty
                if (in_array($t, self::$stopwords)) {
                    $accept = false;
                }
                //}
            //}

            if ($accept) {
                $t = self::_mb_trim($t);
                if (is_numeric($t)) $t = " $t";        // $t ends up as an array index, and numbers just don't work there
                if (!isset($tokens[$t])) {
                    $tokens[$t] = 1;
                } else {
                    $tokens[$t]++;
                }
            }

            $t = strtok("\n\t ");
        }
        return $tokens;
    }

}

?>
