<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Searcher;

/**
 * Stemmer base class for the Finder indexer package.
 *
 * @since  2.5
 */
abstract class Stemmer
{
	/**
	 * An internal cache of stemmed tokens.
	 *
	 * @var    array
	 * @since  2.5
	 */
	public $cache = array();

	/**
	 * Method to get a stemmer, creating it if necessary.
	 *
	 * @param   string  $adapter  The type of stemmer to load.
	 *
	 * @return  FinderIndexerStemmer  A FinderIndexerStemmer instance.
	 *
	 * @since   2.5
	 * @throws  Exception on invalid stemmer.
	 */
	public static function getInstance($adapter)
	{
		static $instances;

		// Only create one stemmer for each adapter.
		if (isset($instances[$adapter]))
		{
			return $instances[$adapter];
		}

		// Create an array of instances if necessary.
		if (!is_array($instances))
		{
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
	 * Method to stem a token and return the root.
	 *
	 * @param   string  $token  The token to stem.
	 * @param   string  $lang   The language of the token.
	 *
	 * @return  string  The root token.
	 *
	 * @since   2.5
	 */
	abstract public function stem($token, $lang='en');
}
