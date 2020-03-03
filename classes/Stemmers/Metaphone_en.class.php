<?php
/**
 * Uses the PHP metaphone() function to find word roots.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (C) 2017-2020 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v1.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher\Stemmers;


/**
 * Metaphone stemmer class for the Searcher plugin.
 * @package searcher
 */
class Metaphone_en extends \Searcher\Stemmer
{
    /**
     * Method to get the metaphone key.
     *
     * @param   string  $token  The token to stem.
     * @param   string  $lang   The language of the token. Unused.
     * @return  string  The root token.
     */
    public function stem($token, $lang='en')
    {
        global $_CONF;

        // Check if the token is long enough to merit stemming.
        if (strlen($token) < self::$min_word_len) {
            return $token;
        }

        // Stem the token if it is not in the cache.
        if (!isset($this->cache[$lang][$token])) {
            // Tokenize and add to the cache.
            $this->cache[$lang][$token] = metaphone($token);
        }
        return $this->cache[$lang][$token];
    }

}

?>
