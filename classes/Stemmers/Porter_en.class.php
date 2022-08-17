<?php
/**
 * Uses the Porter Stemmer algorithm to find word roots.
 *
 * Adapted from Joomla com_finder component.
 * Based on the Porter stemmer algorithm:
 * <https://tartarus.org/martin/PorterStemmer/c.txt>
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (C) 2017-2020 Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (C) 2005-2017 Open Source Matters, Inc. All rights reserved.
 * @package     searcher
 * @version     v1.0.09
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher\Stemmers;


/**
 * Porter English stemmer class for the Searcher indexer package.
 *
 * This class was adapted from one written by Richard Heyes.
 * See copyright and link information above.
 *
 * @package searcher
 */
class Porter_en extends \Searcher\Stemmer
{
    /** Regex for matching a consonant.
     * @var string */
    private static $regex_consonant = '(?:[bcdfghjklmnpqrstvwxz]|(?<=[aeiou])y|^y)';

    /** Regex for matching a vowel
     * @var string */
    private static $regex_vowel = '(?:[aeiou]|(?<![aeiou])y)';


    /**
     * Method to stem a token and return the root.
     *
     * @param   string  $token  The token to stem.
     * @param   string  $lang   The language of the token.
     * @return  string  The root token.
     */
    public function stem($token, $lang='en')
    {
        global $_CONF;

        // Check if the token is long enough to merit stemming.
        if (strlen($token) <= self::$min_word_len) {
            return $token;
        }

        // Check if the language is English or All.
        /*if ($lang !== 'en' && $lang != '*')
        {
            return $token;
        }*/

        // Stem the token if it is not in the cache.
        if (!isset($this->cache[$lang][$token]))
        {
            // Stem the token.
            $result = trim($token);
            $result = self::step1ab($result);
            $result = self::step1c($result);
            $result = self::step2($result);
            $result = self::step3($result);
            $result = self::step4($result);
            $result = self::step5($result);
            $result = trim($result);

            // Add the token to the cache.
            $this->cache[$lang][$token] = $result;
        }
        return $this->cache[$lang][$token];
    }


    /**
     * step1ab() gets rid of plurals and -ed or -ing. e.g.
     *
     * caresses  ->  caress
     * ponies    ->  poni
     * ties      ->  ti
     * caress    ->  caress
     * cats      ->  cat
     *
     * feed      ->  feed
     * agreed    ->  agree
     * disabled  ->  disable
     *
     * matting   ->  mat
     * mating    ->  mate
     * meeting   ->  meet
     * milling   ->  mill
     * messing   ->  mess
     * meetings  ->  meet
     *
     * @param   string  $word  The token to stem.
     * @return  string      The stemmed version of $word
     */
    private static function step1ab($word)
    {
        // Part a
        if (substr($word, -1) == 's')
        {
            self::replace($word, 'sses', 'ss')
            or self::replace($word, 'ies', 'i')
            or self::replace($word, 'ss', 'ss')
            or self::replace($word, 's', '');
        }

        // Part b
        if (substr($word, -2, 1) != 'e' or !self::replace($word, 'eed', 'ee', 0))
        {
            // First rule
            $v = self::$regex_vowel;

            // Words ending with ing and ed
            // Note use of && and OR, for precedence reasons
            if (preg_match("#$v+#", substr($word, 0, -3)) && self::replace($word, 'ing', '')
                or preg_match("#$v+#", substr($word, 0, -2)) && self::replace($word, 'ed', ''))
            {
                // If one of above two test successful
                if (!self::replace($word, 'at', 'ate') and !self::replace($word, 'bl', 'ble') and !self::replace($word, 'iz', 'ize'))
                {
                    // Double consonant ending
                    if (self::doubleConsonant($word) and substr($word, -2) != 'll' and substr($word, -2) != 'ss' and substr($word, -2) != 'zz')
                    {
                        $word = substr($word, 0, -1);
                    }
                    elseif (self::m($word) == 1 and self::cvc($word))
                    {
                        $word .= 'e';
                    }
                }
            }
        }

        return $word;
    }


    /**
     * step1c() turns terminal y to i when there is another vowel in the stem.
     *
     * @param   string  $word  The token to stem.
     * @return  string
     */
    private static function step1c($word)
    {
        $v = self::$regex_vowel;

        if (substr($word, -1) == 'y' && preg_match("#$v+#", substr($word, 0, -1)))
        {
            self::replace($word, 'y', 'i');
        }

        return $word;
    }


    /**
     * step2() maps double suffices to single ones. so -izationi.
     * ( = -ize plus -ation) maps to -ize etc.
     * Note that the string before the suffix must give m() > 0.
     *
     * @param   string  $word  The token to stem.
     * @return  string      The stemmed version.
     */
    private static function step2($word)
    {
        switch (substr($word, -2, 1))
        {
            case 'a':
                self::replace($word, 'ational', 'ate', 0)
                or self::replace($word, 'tional', 'tion', 0);
                break;
            case 'c':
                self::replace($word, 'enci', 'ence', 0)
                or self::replace($word, 'anci', 'ance', 0);
                break;
            case 'e':
                self::replace($word, 'izer', 'ize', 0);
                break;
            case 'g':
                self::replace($word, 'logi', 'log', 0);
                break;
            case 'l':
                self::replace($word, 'entli', 'ent', 0)
                or self::replace($word, 'ousli', 'ous', 0)
                or self::replace($word, 'alli', 'al', 0)
                or self::replace($word, 'bli', 'ble', 0)
                or self::replace($word, 'eli', 'e', 0);
                break;
            case 'o':
                self::replace($word, 'ization', 'ize', 0)
                or self::replace($word, 'ation', 'ate', 0)
                or self::replace($word, 'ator', 'ate', 0);
                break;
            case 's':
                self::replace($word, 'iveness', 'ive', 0)
                or self::replace($word, 'fulness', 'ful', 0)
                or self::replace($word, 'ousness', 'ous', 0)
                or self::replace($word, 'alism', 'al', 0);
                break;
            case 't':
                self::replace($word, 'biliti', 'ble', 0)
                or self::replace($word, 'aliti', 'al', 0)
                or self::replace($word, 'iviti', 'ive', 0);
                break;
        }
        return $word;
    }


    /**
     * step3() deals with -ic-, -full, -ness etc. similar strategy to step2.
     *
     * @param   string  $word  The token to stem.
     * @return  string      The stemmed version.
     */
    private static function step3($word)
    {
        switch (substr($word, -2, 1))
        {
            case 'a':
                self::replace($word, 'ical', 'ic', 0);
                break;
            case 's':
                self::replace($word, 'ness', '', 0);
                break;
            case 't':
                self::replace($word, 'icate', 'ic', 0)
                or self::replace($word, 'iciti', 'ic', 0);
                break;
            case 'u':
                self::replace($word, 'ful', '', 0);
                break;
            case 'v':
                self::replace($word, 'ative', '', 0);
                break;
            case 'z':
                self::replace($word, 'alize', 'al', 0);
                break;
        }
        return $word;
    }


    /**
     * step4() takes off -ant, -ence etc., in context <c>vcvc<v>.
     *
     * @param   string  $word  The token to stem.
     * @return  string      The stemmed version.
     */
    private static function step4($word)
    {
        switch (substr($word, -2, 1))
        {
            case 'a':
                self::replace($word, 'al', '', 1);
                break;
            case 'c':
                    self::replace($word, 'ance', '', 1)
                or self::replace($word, 'ence', '', 1);
                break;
            case 'e':
                self::replace($word, 'er', '', 1);
                break;
            case 'i':
                self::replace($word, 'ic', '', 1);
                break;
            case 'l':
                self::replace($word, 'able', '', 1)
                or self::replace($word, 'ible', '', 1);
                break;
            case 'n':
                self::replace($word, 'ant', '', 1)
                or self::replace($word, 'ement', '', 1)
                or self::replace($word, 'ment', '', 1)
                or self::replace($word, 'ent', '', 1);
                break;
            case 'o':
                if (substr($word, -4) == 'tion' or substr($word, -4) == 'sion')
                {
                    self::replace($word, 'ion', '', 1);
                }
                else
                {
                    self::replace($word, 'ou', '', 1);
                }
                break;
            case 's':
                self::replace($word, 'ism', '', 1);
                break;
            case 't':
                    self::replace($word, 'ate', '', 1)
                or self::replace($word, 'iti', '', 1);
                break;
            case 'u':
                self::replace($word, 'ous', '', 1);
                break;
            case 'v':
                self::replace($word, 'ive', '', 1);
                break;
            case 'z':
                self::replace($word, 'ize', '', 1);
                break;
        }
        return $word;
    }


    /**
     * step5() removes a final -e if m() > 1, and changes -ll to -l if m() > 1.
     *
     * @param   string  $word  The token to stem.
     * @return  string      The stemmed version.
     */
    private static function step5($word)
    {
        // Part a
        if (substr($word, -1) == 'e')
        {
            if (self::m(substr($word, 0, -1)) > 1)
            {
                self::replace($word, 'e', '');
            }
            elseif (self::m(substr($word, 0, -1)) == 1)
            {
                if (!self::cvc(substr($word, 0, -1)))
                {
                    self::replace($word, 'e', '');
                }
            }
        }

        // Part b
        if (self::m($word) > 1 and self::doubleConsonant($word) and substr($word, -1) == 'l')
        {
            $word = substr($word, 0, -1);
        }

        return $word;
    }


    /**
     * Replaces the first string with the second, at the end of the string.
     * If third arg is given, then the preceding string must match that m count
     * at least.
     *
     * @param   string  &$str   String to check
     * @param   string  $check  Ending to check for
     * @param   string  $repl   Replacement string
     * @param   integer $m      Optional minimum number of m() to meet
     * @return boolean Whether the $check string was at the end
     *                   of the $str string. True does not necessarily mean
     *                   that it was replaced.
     */
    private static function replace(&$str, $check, $repl, $m = null)
    {
        $len = 0 - strlen($check);

        if (substr($str, $len) == $check) {
            $substr = substr($str, 0, $len);

            if (is_null($m) or self::m($substr) > $m) {
                $str = $substr . $repl;
            }

            return true;
        }

        return false;
    }


    /**
     * m() - measures the number of consonant sequences in $str.
     * if c is a consonant sequence and v a vowel sequence, and <..> indicates
     * arbitrary presence,
     *
     * - <c><v>       gives 0
     * - <c>vc<v>     gives 1
     * - <c>vcvc<v>   gives 2
     * - <c>vcvcvc<v> gives 3
     *
     * @param  string  $str  The string to return the m count for
     * @return integer The m count
     */
    private static function m($str)
    {
        $c = self::$regex_consonant;
        $v = self::$regex_vowel;

        $str = preg_replace("#^$c+#", '', $str);
        $str = preg_replace("#$v+$#", '', $str);

        preg_match_all("#($v+$c+)#", $str, $matches);

        return count($matches[1]);
    }


    /**
     * Returns true/false as to whether the given string contains two
     * of the same consonant next to each other at the end of the string.
     *
     * @param   string  $str  String to check
     * @return  boolean Result
     */
    private static function doubleConsonant($str)
    {
        $c = self::$regex_consonant;

        return preg_match("#$c{2}$#", $str, $matches) and $matches[0][0] == $matches[0][1];
    }


    /**
     * Checks for ending CVC sequence where second C is not W, X or Y.
     *
     * @param   string  $str  String to check
     * @return  boolean Result
     */
    private static function cvc($str)
    {
        $c = self::$regex_consonant;
        $v = self::$regex_vowel;

        return preg_match("#($c$v$c)$#", $str, $matches)
            && strlen($matches[1]) == 3
            && $matches[1][2] != 'w'
            && $matches[1][2] != 'x'
            && $matches[1][2] != 'y';
    }

}

?>
