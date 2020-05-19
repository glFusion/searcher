<?php
/**
 * Uses the Libs stemmer from Wordpress.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (C) 2020 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v1.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher\Stemmers;


/**
 * Libs stemmer class for the Searcher plugin.
 * @package searcher
 */
class Libs extends \Searcher\Stemmer
{
    /** First exception set.
     * @var array */
    private static $exceptions1 = array(
        'skis'      => 'ski',
        'skies'     =>'sky',
        'dying'     =>'die',
        'lying'     =>'lie',
        'tying'     =>'tie',
        'idly'      =>'idl',
        'gently'    =>'gentl',
        'ugly'      =>'ugli',
        'early'     =>'earli',
        'only'      =>'onli',
        'singly'    =>'singl',
        'sky'       =>'sky',
        'news'      =>'news',
        'howe'      =>'howe',
        'atlas'     =>'atlas',
        'cosmos'    =>'cosmos',
        'bias'      =>'bias',
        'andes'     =>'andes',
    );

    /** Second exception set.
     * @var array */
    private static $exceptions2 = array(
        'inning',
        'outing',
        'canning',
        'herring',
        'earring',
        'proceed',
        'exceed',
        'succeed',
    );

    /** Vowel regex.
     * @var string */
    private static $vowel = '([aeiouy]){1}';

    /** Consonant regex, including "y".
     * @var string */
    private static $consonant = '([bcdfghjklmnpqrstvwxzY]){1}';

    /** Short consonant regex, excludes "y".
     * @var string */
    private static $consonant_short = '([bcdfghjklmnpqrstvz]){1}';

    /** Double letters.
     * @var string */
    private static $double = '((bb)|(dd)|(ff)|(gg)|(mm)|(nn)|(pp)|(rr)|(tt))';

    /** Region after the first non-vowel following a vowel
     * @var string */
    private static $r1 = "(?<=([aeiouy]){1}([bcdfghjklmnpqrstvwxzY]){1})[a-zY']*\$";

    /** Exceptions to the above region.
     * @var string */
    private static $r1_exceptions = "((?<=^commun)|(?<=^gener)|(?<=^arsen))[a-zY']*\$";

    /** Region after the first non-vowel following a vowel in R1.
     * @var string */
    private static $r2 = "(?<=([aeiouy]){1}([bcdfghjklmnpqrstvwxzY]){1})[a-zY']*\$";

    /** Placeholder for region 1.
     * @var string */
    private $R1 = "";

    /** Placeholder for region 2.
     * @var string */
    private $R2 = "";


    /**
     * Method to get the metaphone key.
     *
     * @param   string  $word   The token to stem
     * @param   string  $lang   The language of the token. Unused.
     * @return  string          The root token
     */
    public function stem($word, $lang='en')
    {
        $word = strtolower($word);

        // Initialize the cache array
        if (!isset($this->cache[$lang])) {
            $this->cache[$lang] = array();
        }
        // Stem the token if it is not in the cache.
        if (!isset($this->cache[$lang][$word])) {
            if (strlen($word) < 3) {
                $this->cache[$lang][$word] = $word;
            } else if (key_exists($word, self::$exceptions1)) {
                $this->cache[$lang][$word] = self::$exceptions1[$word];
            } else {
                $word = $this->markVowels($word);
                $word = $this->step0($word);
                $word = $this->step1($word);

                if (!in_array($word, self::$exceptions2)) {
                    $word = $this->step2($word);
                    $word = $this->step3($word);
                    $word = $this->step4($word);
                    $word = $this->step5($word);
                }
                $this->cache[$lang][$word] = strtolower($this->endsWithI($word));
            }
        }
        return $this->cache[$lang][$word];
    }


    /**
     * Remove a trailing letter "i" if the word ends with it.
     *
     * @param   string  $word   Word to check
     * @return  string          Word with the suffix removed
     */
    private function endsWithI($word)
    {
        $suffix = 'i';
        if ($this->endsWith($suffix, $word)) {
            self::trimR($word, strlen($suffix));
        }
        return $word;
    }


    /**
     * Check if the word ends with a short syllable.
     *
     * @param   string  $word   Original word
     * @return  boolean         True if it ends with a short syllable
     */
    private function endsWithShortSyllable($word)
    {
        $c = self::$consonant;
        $c2 = self::$consonant_short;
        $v = self::$vowel;

        if (strlen($word)<3 && preg_match("#$v#", $word{0})  && preg_match("#$c#", $word{1})){
            return true;
        } else{
            if (
                preg_match("#$c2#", substr($word,-1)) &&
                (preg_match("#$v#", substr($word, -2, 1)) || substr($word, -2, 1) == 'y') &&
                preg_match("#$c#", substr($word, -3, 1))
            ){
                return true;
                }
            }
        return false;
    }


    /**
     * Determines if word is short
     *
     * @param string $word String to stem
     * @return boolean
     */
    private function isShort($word)
    {
        $this->updateR1R2($word);

        if ($this->R1 == "") {
            if (preg_match("#^".self::$vowel.self::$consonant."#", $word)) {
                return true;
            } else if (preg_match("#".self::$vowel.self::$consonant_short."\$#", $word)){
                return true;
            }
        }
        return false;
    }


    private function markVowels($word)
    {
        $c = self::$consonant;
        $v = self::$vowel;
        for ($i = 0; $i < strlen($word); $i++) {
            $char = $word{$i};
            if (
                $char == 'y' &&
                (
                    $i == 0 ||
                    (
                        $i > 0 && preg_match("#$v#", $word{$i-1})
                    )
                )
            ) {
                $word{$i} = 'Y';
            }
        }
        $this->updateR1R2($word);
        return $word;
    }

    /**
     * Updates R1 and R2
     *
     * @param string $word String to stem
     */
    private function updateR1R2($word)
    {
        preg_match("#".self::$r1_exceptions."#", $word, $matches, PREG_OFFSET_CAPTURE);
        if (sizeof($matches) == 0) {
            preg_match("#".self::$r1."#", $word, $matches, PREG_OFFSET_CAPTURE);
        }
        $this->R1 = (sizeof($matches) > 0 ? $matches[0][0] : "");

        preg_match("#".self::$r2."#", $this->R1, $matches, PREG_OFFSET_CAPTURE);
        $this->R2 = (sizeof($matches) > 0 ? $matches[0][0] : "");
    }


    /**
     * Determines if $word ends with $suffix.
     *
     * @param   string  $suffix Suffix to check
     * @param   string  $word   Word in which to check for $suffix
     * @return  boolean     True if $word ends with $suffix
     */
    private function endsWith($suffix, $word)
    {
        if (substr($word, -strlen($suffix)) == $suffix){
            return true;
        }
        return false;
    }


    /**
     * Trim trailing characters from a word.
     *
     * @param   string  $word   Original word
     * @param   integer $n      Number of characters to trim
     * @return  string      Trimmed word
     */
    private static function trimR(&$word, $n)
    {
        $word = substr($word, 0, strlen($word)-$n);
        return $word;
    }


    /**
     * Step 0
     *
     * @param string $word String to stem
     * @return string
     */
    private function step0($word)
    {
        if (substr($word, 0, 1) == "'") {
            $word = substr($word, 1);
        }

        $suffixes = Array("'s'", "'s", "'");
        foreach ($suffixes as $suffix) {
            if ($this->endsWith($suffix, $word)) {
                self::trimR($word, strlen($suffix));
            }
        }
        return $word;
    }


    /**
     * Step 1
     *
     * @param string $word String to stem
     * @return string
     */
     private function step1($word)
     {
        $c = self::$consonant;
        $v = self::$vowel;

        //step 1a
        if ($this->endsWith("sses", $word)) {
            $word = substr($word, 0, strlen($word)-4)."ss";
        } elseif (
            $this->endsWith("ied", $word) ||
            $this->endsWith("ies", $word)
        ) {
            $word = substr($word, 0, strlen($word)-3);
            if (strlen($word) > 1) {
                $word .= "i";
            } else {
                $word .= "ie";
            }
        } elseif (
            $this->endsWith("s", $word) &&
            substr($word, -2, 1) != "s" &&
            substr($word, -2, 1) != "u"
        ) {
            $part = substr($word, 0, strlen($word)-2);
            if (preg_match("#$v#", $part)) {
                self::trimR($word, 1);
            }
        }

        $found = false;
        if (in_array($word, self::$exceptions2)) {
            return $word;
        }

        //step 1b
        $suffixes = array(
            "eedly" => "ee",
            "eed"   => "ee",
        );
        foreach ($suffixes as $suffix => $replacement) {
            if (substr($word, -strlen($suffix)) == $suffix){
                $found = true;
                if (strpos($this->R1, $suffix) > -1){
                    $word = self::trimR($word, strlen($suffix)).$replacement;
                    break;
                }
            }
        }

        $suffixes = array(
            "ingly",
            "edly",
            "ing",
            "ed",
        );
        if (!$found){
            foreach ($suffixes as $suffix) {
                if (
                    substr($word, -strlen($suffix)) == $suffix &&
                    preg_match("#$v#", substr($word, 0, strlen($word)- strlen($suffix)))
                ){
                    $word = self::trimR($word, strlen($suffix));
                    if (
                        substr($word, -2) == "at" ||
                        substr($word, -2) == "bl" ||
                        substr($word, -2) == "iz"
                    ) {
                        $word .= "e";

                    } elseif (preg_match("#".self::$double."\$#", $word)) {
                        $word = self::trimR($word, 1);
                    } elseif ($this->isShort($word)) {
                        $word .= "e";
                    }
                    break;
                }
            }
        }

        //step 1c
        if (
            substr($word, -1) == "y" OR substr($word, -1) == "Y" &&
            preg_match("#$c#", substr($word, -2, 1)) &&
            strlen($word) > 2
        ) {
            $word = self::trimR($word, 1)."i";
        }
        return $word;
    }


    /**
     * Step 2
     *
     * @param string $word String to stem
     * @return string
     */
    private function step2($word)
    {
        $this->updateR1R2($word);

        $suffixes = array(
            "ization"   => "ize",
            "fulness"   => "ful",
            "ousness"   => "ous",
            "iveness"   => "ive",
            "ational"   => "ate",
            "biliti"    => "ble",
            "tional"    => "tion",
            "lessli"    => "less",
            "ation"     => "ate",
            "alism"     => "al",
            "aliti"     => "al",
            "ousli"     => "ous",
            "iviti"     => "ive",
            "fulli"     => "ful",
            "entli"     => "ent",
            "enci"      => "ence",
            "anci"      => "ance",
            "abli"      => "able",
            "izer"      => "ize",
            "ator"      => "ate",
            "alli"      => "al",
            "bli"       => "ble",
            "ogi"       => "og",
        );

        $found = false;
        foreach ($suffixes as $suffix => $newSuffix) {
            if ($this->endsWith($suffix, $word)) {
                $found=true;
                if (strpos($this->R1, $suffix) > -1) {
                    if ($suffix == 'ogi'){
                        if (substr($word, -4, 1) == 'l') {
                            //special ogi case
                            $word = self::trimR($word, strlen($suffix)).$newSuffix;
                        }
                    } else {
                        $word = self::trimR($word, strlen($suffix)).$newSuffix;
                    }
                }
                break;
            }
        }

        if (!$found && strpos($this->R1, "li") > -1) {
            $word = preg_replace("#(?<=[cdeghkmnrt])li$#", "", $word);
        }

        return $word;
    }


    /**
     * Step 3
     *
     * @param string $word String to stem
     * @return string
     */
    private function step3($word)
    {
        $this->updateR1R2($word);

        $suffixes = array(
            "ational"   => "ate",
            "tional"    => "tion",
            "alize"     => "al",
            "icate"     => "ic",
            "ative"     => "",
            "iciti"     => "ic",
            "ical"      => "ic",
            "ness"      => "",
            "ful"       => "",
        );

        foreach ($suffixes as $suffix => $newSuffix) {
            if ($this->endsWith($suffix, $word)) {
                if (strpos($this->R1, $suffix) > -1) {
                    if ($suffix == 'ative') {
                        if (strpos($this->R2, $suffix) > -1) {
                            //special 'active' case
                            $word = self::trimR($word, strlen($suffix)).$newSuffix;
                        }
                    } else{
                        $word = self::trimR($word, strlen($suffix)).$newSuffix;
                    }
                }
                break;
            }
        }
        return $word;
    }


    /**
     * Step 4
     *
     * @param string $word String to stem
     * @return string
     */
    private function step4($word)
    {
        $this->updateR1R2($word);
        $suffixes = array(
            "iveness"   => "",
            "ement"     => "",
            "ance"      => "",
            "ence"      => "",
            "able"      => "",
            "ible"      => "",
            "ant"       => "",
            "ment"      => "",
            "ent"       => "",
            "ism"       => "",
            "ate"       => "",
            "iti"       => "",
            "ous"       => "",
            "ive"       => "",
            "ize"       => "",
            "ion"       => "",
            "al"        => "",
            "er"        => "",
            "ic"        => ""
        );
        $precededBy = array(
            "ion"   => "s,t"
        );
        
        $found = false;
        foreach ($suffixes as $suffix => $newSuffix) {
            if ($this->endsWith($suffix, $word) && !$found) {
                if (strpos($this->R2, $suffix) > -1) {
                    if (key_exists($suffix, $precededBy)) {
                        $parts = explode(",",$precededBy[$suffix]);
//                        $parts = split(',', $precededBy[$suffix]);
                        foreach ($parts as $part) {
                            if (substr($word, -(strlen($suffix)+1), strlen($part)) == $part){
                                $word = self::trimR($word, strlen($suffix)).$newSuffix;
                                break;
                            }
                        }
                    } else{
                        $word = self::trimR($word, strlen($suffix)).$newSuffix;
                    }
                }
                break;
            }
        }
        return $word;
    }


    /**
     * Step 5
     *
     * @param string $word String to stem
     * @return string
     */
    private function step5($word)
    {
        $this->updateR1R2($word);
        if (
            (
                $this->endsWith("e", $word) &&
                strpos($this->R2, "e") > -1
            )
            ||
            (
                $this->endsWith("e", $word) &&
                strpos($this->R1, "e") > -1 &&
                !$this->endsWithShortSyllable(substr($word, 0, strlen($word)-1))
            )
        ) {
            self::trimR($word, 1);
        } elseif (
            $this->endsWith("l", $word) &&
            strpos($this->R2, "l") > -1 &&
            substr($word, -2, 1) == "l"
        ){
            self::trimR($word, 1);
        }
        return $word;
    }
}

?>
