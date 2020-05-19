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
class Stemmer
{
    /** An internal cache of stemmed tokens.
     * @var array */
    public $cache = array();

    /** Minimum word length to consider when indexint.
     * @var integer */
    protected static $min_word_len = 3;

    /** Name of the stemmer class, populated if instantiated.
     * If the name remains 'Stemmer' then the requested stemmer was not found.
     * @var string */
    protected $name = 'Stemmer';


    /**
     * Set the instantiated stemmer name into a local property.
     */
    public function __construct()
    {
        $this->name = (new \ReflectionClass($this))->getShortName();
    }


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
            return new self;
        }
        return $instances[$adapter];
    }


    /**
     * Method to stem a token and return the root.
     * This default function just returns the token to prevent errors if
     * the requested stemmer is unavailable.
     *
     * @param   string  $token  The token to stem.
     * @param   string  $lang   The language of the token.
     * @return  string  The root token.
     */
    public function stem($token, $lang='en')
    {
        return $token;
    }


    /**
     * Check if a valid stemmer was instantiated.
     * Used in case an invalid stemmer is requested or none is configured.
     *
     * @return  boolean     True if valid, False if not
     */
    public function isValid()
    {
        return $this->name !== 'Stemmer' ? true : false;
    }

}

?>
