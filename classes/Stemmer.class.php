<?php
/**
 * Wrapper class for a word stemmer.
 * Creates or gets the current instance of the Stemmer class.
 * Adapted from Joomla com_finder component
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2018 Lee Garner
 * @copyright   Copyright (c) 2005-2017 Open Source Matters, Inc. All rights reserved.
 * @package     searcher
 * @version     v0.1.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher;

/**
 * Stemmer base class for the Searcher indexer package.
 * @package Searcher
 */
abstract class Stemmer
{
    /**
     * An internal cache of stemmed tokens.
     * @var array */
    public $cache = array();

    /**
     * Minimum word length to consider when indexint.
     * @var integer */
    protected static $min_word_len = 3;


    /**
    * Method to get a stemmer, creating it if necessary.
    *
    * @param    string  $adapter    The type of stemmer to load.
    * @return   object      Stemmer object
    */
    public static function getInstance($adapter)
    {
        static $instances = array();

        // Only create one stemmer for each adapter.
        if (isset($instances[$adapter])) {
            return $instances[$adapter];
        }

        // Setup the adapter for the stemmer.
        $adapter = ucfirst($adapter);
        $class = __NAMESPACE__ . '\\Stemmers\\' . $adapter;

        // Instantiate the stemmer.
        if (class_exists($class)) {
            $instances[$adapter] = new $class;
        } else {
            COM_errorLog("Searcher:: Stemmer class $class not found");
            return NULL;
        }
        return $instances[$adapter];
    }


    /**
     * Method to stem a token and return the root.
     *
     * @param   string  $token  The token to stem.
     * @param   string  $lang   The language of the token.
     * @return  string  The root token.
     */
    abstract public function stem($token, $lang='en');

}

?>
