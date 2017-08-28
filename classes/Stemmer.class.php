<?php
/**
*   Wrapper class for a word stemmer.
*   Creates or gets the current instance of the Stemmer class.
*   Adapted from Joomla com_finder component
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner
*   @copyright  Copyright (c) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
*   @package    searcher
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Searcher;

/**
*   Stemmer base class for the Finder indexer package.
*   @package Searcher
*/
abstract class Stemmer
{
    /**
    *   An internal cache of stemmed tokens.
    *   @var    array
    */
    public $cache = array();


    /**
    *   Method to get a stemmer, creating it if necessary.
    *
    *   @param  string  $adapter    The type of stemmer to load.
    *
    *   @return object      Stemmer object
    */
    public static function getInstance($adapter)
    {
        static $instances = NULL;

        // Only create one stemmer for each adapter.
        if (isset($instances[$adapter])) {
            return $instances[$adapter];
        }

        // Create an array of instances if necessary.
        if (!is_array($instances)) {
            $instances = array();
        }

        // Setup the adapter for the stemmer.
        $adapter = ucfirst($adapter);
        $path = __DIR__ . '/stemmer/' . $adapter . '.class.php';
        $class = __NAMESPACE__ . '\\Stemmer' . $adapter;

        // Check if a stemmer exists for the adapter.
        if (!file_exists($path)) {
            COM_errorLog("Searcher:: Stemmer $adapter not found");
            return NULL;
        } else {
            // Instantiate the stemmer.
            include_once $path;
            if (class_exists($class)) {
                $instances[$adapter] = new $class;
            } else {
                COM_errorLog("Searcher:: Stemmer class $class not found");
                return NULL;
            }
        }
        return $instances[$adapter];
    }

    /**
    *   Method to stem a token and return the root.
    *
    *   @param  string  $token  The token to stem.
    *   @param  string  $lang   The language of the token.
    *   @return string  The root token.
    */
    abstract public function stem($token, $lang='en');

}

?>
