<?php

use Enrise\Extendable;

class Enrise_String extends Enrise_ValueAbstract Implements Countable, Enrise_SharedInterface
{
    const EMPTY_TRANSLATION_KEY = 'empty_string';

    protected $_reference = true;

    protected static $_debug = array();

    const ALPHA_CHARS = 'abcdefghijklmnopqrstuvwxyz';
    const ALPHA_NUM_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    public function __construct($value)
    {
        $this->_setValue($value);
    }

    public function highlight($term)
    {
        $isExtended = false;
        if ($term instanceof Enrise_Regex) {

        }
        if ($term instanceof self) {
            $term = $term->render();
        }
        if (!is_string($term)) {
            throw new InvalidArgumentException('Term must be a string!');
        }
        /*if () {

        }*/
        return $this->_create($term);
    }

    /**
     * Interface method for easy acces
     *
     * @return int
     */
    public function count()
    {
        return $this->_length;
        //@todo, is this still logical or should be return the length of the string?
        //Wordcount vs length
        return $this->wordCount(0);
    }

    /**
     *
     * @param $format
     * @param $charlist
     * @return Enrise_Array|int
     */
    public function wordCount($format = 1, $charlist = null)
    {
        $data = str_word_count($this->_value, $format, $charlist);
        if (0 === $format) {
            return new Enrise_Int($data);
        }
        return new Enrise_Array($data);
    }

    /**
     * Variable options to count characters
     *
     * @see http://nl.php.net/count_chars
     * @param int $mode
     * @return Enrise_Array
     */
    public function charCount($mode = 0)
    {
        $data = count_chars($this->_value, $mode);
        if (3 == $mode || 4 == $mode) {
            $data = str_split($data, 1);
        }
        return new Enrise_Array($data);
    }

    /**
     * Explode a string by the given delimiter into an Enrise_Array
     *
     * @param Enrise_String|string $delimiter
     * @return Enrise_Array
     */
    public function explode($delimiter)
    {
        if ($delimiter instanceof self) {
            $delimiter = $delimiter->getValue();
        }
        if (!is_scalar($delimiter)) {
            throw new InvalidArgumentException('Delimiter must be scalar!');
        }
        return new Enrise_Array(explode($delimiter, $this->getValue()));
    }

    /**
     * Check if the given value occurs in this string
     *
     * @param Enrise_String|string $value
     * @return Enrise_Float
     */
    public function indexOf($value)
    {
        if (!$value instanceof self) {
            $value = new self($value);
        }
        $value = mb_strpos($this->_value, $value->getValue());
        if (false === $value) {
            $value = -1;
        }
        return new Enrise_Float($value);
    }

    /**
     * Find the last position of the given value if available
     *
     * @param Enrise_String|string $value
     * @return Enrise_Float
     */
    public function lastIndexOf($value)
    {
        if (!is_string($value)) {
            $value = new Enrise_String($value);
        }
        $value = mb_strrpos($this->_value, $value->getValue());
        if (false === $value) {
            $value = -1;
        }
        return new Enrise_Float($value);
    }

    public function inArray($value, $strict = false)
    {
        throw new Exception('Not yet implemented!');
    }

    /**
     * Extended string search function and returns the first match's position
     *
     * @param Enrise_Regex|Enrise_String|string $search
     * @return Enrise_Float
     */
    public function search($search)
    {
        if (!$search instanceof Enrise_String) {
            $search = new Enrise_String($search);
        }
        if ($search instanceof Enrise_Regex) {
            $matches = array();
            preg_match($search->getRegex(), preg_quote($this->_value), $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0], $matches[0][1])) {
                return new Enrise_Float($matches[0][1]);
            }
        } else if ($search instanceof Enrise_String) {
            return $this->indexOf($search);
        }
        return new Enrise_Float(-1);
    }

    /**
     *
     * @param Enrise_String|Enrise_Regex|string $value
     * @param mixed $modifiers
     * @return Enrise_Array
     */
    public function match($value, $modifiers = null)
    {
        if (!$value instanceof Enrise_Regex) {
            if ($value instanceof Enrise_String) {
                $value = $value->getValue();;
            }
            $value = new Enrise_Regex($value);
        }
        $value->setModifiers($modifiers);
        $matches = array();
        preg_match_all($value->getRegex(), $this->_value, $matches);
        return new Enrise_Array($matches[0]);
    }

    /**
     * Wrapper class to accomodate multiple cases in one
     *
     * @param Enrise_String|Enrise_Regex $search
     * @param Enrise_String $replace
     * @return Enrise_String
     */
    public function replace($search, $replace)
    {
        if (!$replace instanceof Enrise_String) {
            $replace = new Enrise_String($replace);
        }
        if ($search instanceof Enrise_Regex) {
            return $this->_create(preg_replace($search->getValue(), $replace->getValue(), $this->_value));
        }
        if ($search instanceof Enrise_String) {
            return $this->_create(str_replace($search->getValue(), $replace->getValue(), $this->_value));
        }
        return $this;
    }

    /**
     * Substring this value
     *
     * @param Enrise_Int|numeric $from
     * @param Enrise_Int|numeric $to
     * @return Enrise_String
     */
    public function substring($from, $to = null)
    {
        if ($from instanceof Enrise_Int) {
            $from = $from->getValue();
        }
        if ($to instanceof Enrise_Int) {
            $to = $to->getValue();
        }
        if (empty($to)) {
            $to = $this->length;
        }
        return $this->_create(mb_substr($this->_value, $from, $to));
    }

    /**
     * Split a string into smaller chunks
     *
     * @param int $length default 76
     * @param $end default PHP_EOL
     * @return Enrise_String
     * @see http://nl3.php.net/manual/en/function.chunk-split.php
     */
    public function chunckSplit($length = 76, $end = PHP_EOL)
    {
        $length = (int) $length;
        if ($end instanceof Enrise_Array) {
            $end = $end->implode('');
        }
        $end = (string) $end;
        return $this->_create(chunk_split($this->_value, $length, $end));
    }

    /**
     * Convert a string to an array
     *
     * @param int $length
     * @return Enrise_Array
     */
    public function split($length = 1)
    {
        $length = (int) $length;
        if (1 > $length) {
            $length = $this->_length;
        }
        return new Enrise_Array(str_split($this->_value, $length));
    }

    /**
     * Return the value of this object uppercased
     *
     * @return Enrise_String
     */
    public function uppercase()
    {
        return $this->_create(mb_strtoupper($this->_value));
    }

    /**
     * Return the value of this object lowercases
     *
     * @return Enrise_String
     */
    public function lowercase()
    {
        return $this->_create(mb_strtolower($this->_value));
    }

    /**
     * Compare 2 strings
     *
     * @param Enrise_String $value
     * @param bool $caseSensitive
     * @return Enrise_Float
     */
    public function compare($value, $caseSensitive = true)
    {
        if ($value instanceof self) {
            $value = $this->getValue();
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException('Value must be a string');
        }
        if ($caseSensitive) {
            return strcmp($this->getValue(), $value);
        }
        return new Enrise_Float(strcasecmp($this->getValue(), $value));
    }

    /**
     * Normal sscanf function
     */
    public function sscanf($format)
    {
        if ($format instanceof self) {
            $format = $format->getValue();
        } else if (is_object($format) && method_exists($format, '__toString')) {
            $format = (string) $format;
        }
        if (!is_string($format)) {
            throw new InvalidArgumentException('Format must be a string!');
        }
        return new Enrise_Array(sscanf($this->_value, $format));
    }

    /**
     * Uppercase the first character of each word in a string
     *
     * @return Enrise_String
     */
    public function uppercaseWords()
    {
        return $this->_create(ucwords($this->_value));
    }

    /**
     * Sprintf function
     *
     * @return Enrise_String
     */
    public function sprintf()
    {
        $args = func_get_args();
        $data = array();
        foreach ($args as $k => $v) {
            if ($v instanceof Enrise_Array) {
                $data = array_merge($data, $v->getValue());
            } else if (is_array($v)) {
                $data = array_merge($data, $v);
            } else if (is_string($v)) {
                $data[] = $v;
            }
        }
        return $this->_create(vsprintf($this->_value, $data));
    }

    /**
     * Dynamic sprintf with assoc replacement
     * Syntax: %(foo)s would require as input: array('foo' => 'bar')
     * %(baz)d would require as input array('baz' => 10);
     *
     * All other sprintf replacement still work as expected
     *
     * @param $data
     * @return Enrise_String
     */
    public function dsprintf($data)
    {
        $used = array();
        // get the matches, and feed them to our function
        $string = preg_replace('/\%\((.*?)\)(.)/e', '$this->_dsprintfMatch(\'$1\',\'$2\',\$data,$used)', $this->_value);
        $data = array_diff_key($data, $used); // diff the data with the used_keys

        $typeSpecifiers = array(
            'b', 'c', 'd', 'e', 'E', 'f', 'F', 'g', 'G', 'o', 'u', 's', 'x', 'X'
        );
        //Prepare a control regex to see how many %specifiers are left
        $control = '%' . implode('|%', $typeSpecifiers);
        preg_match_all('~(' . $control . ')~', $string, $matches);
        //If it doesn't match pad the feed array to prevent notices
        if (count($data) < count($matches)) {
            $data = array_pad($data, count($matches), '');
        }
        return $this->_create(trim(vsprintf($string, $data))); // yeah!
    }

    /**
     *
     * @param unknown_type $m1
     * @param unknown_type $m2
     * @param unknown_type $data
     * @param unknown_type $used
     */
    protected function _dsprintfMatch($m1, $m2, &$data, &$used)
    {
        if (isset($data[$m1])) { // if the key is there
            $str = $data[$m1];
            $used[$m1] = $m1; // dont unset it, it can be used multiple times
            return sprintf("%".$m2,$str); // sprintf the string, so %s, or %d works like it should
        }
        return "%".$m2; // else, return a regular %s, or %d or whatever is used
    }

    /**
     * Concat all given function arguments
     *
     * @return Enrise_String
     */
    public function concat()
    {
        return call_user_func_array(array($this, 'merge'), func_get_args());
    }

    /**
     * Concat all given function arguments
     *
     * @return Enrise_String
     */
    public function merge()
    {
        $data = '';
        foreach (func_get_args() as $arg) {
            if (!$arg instanceof Enrise_ValueAbstract) {
                switch (gettype($data)) {
                    case 'string':
                        $arg = new self($arg);
                        break;
                    case 'array':
                        $arg = new Enrise_Array($arg);
                        break;
                    case 'object':
                        if (method_exists($arg, '__toArray')) {
                            $arg = new Enrise_Array($arg->__toArray());
                        } else if (method_exists($arg, 'toArray')) {
                            $arg = new Enrise_Array($arg->toArray());
                        } else if (method_exists($arg, '__toString')) {
                            $arg = new self($arg->__toString());
                        }
                }
            }
            $data .= $arg->getValue();
        }
        return $this->_create($this->_value . $data);

    }

    /**
     * URL encode this string
     *
     * @param bool $raw
     * @return Enrise_String
     */
    public function urlencode($raw = false)
    {
        $function = 'urlencode';
        if (true === $raw) {
            $function = 'rawurlencode';
        }
        return $this->_simple($function);
    }

    /**
     * URL decode this string
     *
     * @param bool $raw
     * @return Enrise_String
     */
    public function urldecode($raw = false)
    {
        $function = 'urldecode';
        if (true === $raw) {
            $function = 'rawurldecode';
        }
        return $this->_simple($function);
    }

    /**
     * Base64 encode this string
     *
     * @return Enrise_String
     */
    public function base64Encode()
    {
        return $this->_simple('base64_encode');
    }

    /**
     * Base64 decode this string
     *
     * @return Enrise_String
     */
    public function base64Decode()
    {
        return $this->_simple('base64_decode');
    }

    /**
     * MD5 hash this string
     *
     * @param bool $raw
     * @return Enrise_String
     */
    public function md5($raw = false)
    {
        $raw = (bool) $raw;
        return $this->_create(md5($this->_value, $raw));
    }

    /**
     * Calculate the crc32 value of this string
     *
     * @return Enrise_String
     */
    public function crc32()
    {
        return $this->_simple(__FUNCTION__);
    }

    /**
     * Return the SHA1 value of this string
     *
     * @return Enrise_String
     */
    public function sha1()
    {
        return $this->_simple(__FUNCTION__);
    }

    /**
     * Return the rot13 value of this string
     *
     * @return Enrise_String
     */
    public function rot13()
    {
        return $this->_simple('str_rot13');
    }

    /**
     * Returns this string shuffled
     *
     * @return Enrise_String
     */
    public function shuffle()
    {
        return $this->_simple('str_shuffle');
    }

    /**
     * Return a random string
     *
     * @return Enrise_String
     */
    public function random($min = 1, $max = null)
    {
        if (is_object($min) && method_exists($min, '__toString')) {
            $min = $min->__toString();
        }
        if (is_object($min) && method_exists($max, '__toString')) {
            $max = $max->__toString();
        }
        if (!is_numeric($min)) {
            throw new InvalidArgumentException('Param $min must be numeric!');
        }
        if (null === $max) {
            $max = max(array($min + 1, 255));
        }
        if (!is_numeric($max)) {
            throw new InvalidArgumentException('Param $max must be numeric!');
        }
        if ($min > $max) {
            throw new InvalidArgumentException('Min is larger then max');
        }
        $tmp = new self($this->_getCharSet());
        return $this->_create($tmp->shuffle()->substring(0, mt_rand($min, $max))->getValue());
    }

    /**
     * Return this string reversed
     *
     * @return Enrise_String
     */
    public function reverse()
    {
        return $this->_simple('strrev');
    }

    /**
     * Helper method for easy function calls
     *
     * @param string $function
     * @return Enrise_String
     */
    protected function _simple($function)
    {
        return $this->_create($function($this->_value));
    }

    /**
     * Return substring part or empty string if needle is not found.
     * Case sensative
     *
     * @param Enrise_String|string $needle
     * @param bool $beforeNeedle
     * @return Enrise_String
     */
    public function strstr($needle, $beforeNeedle = false)
    {
        if ($needle instanceof self) {
            $needle = $needle->getValue();
        }
        if (!is_scalar($needle)) {
            throw new InvalidArgumentException('Needle must be scalar!');
        }
        $beforeNeedle = (bool) $beforeNeedle;
        $value = strstr($this->_value, $needle, $beforeNeedle);
        if (!$value) {
            $value = '';
        }
        return $this->_create($value);
    }

    /**
     * Return substring part or empty string if needle is not found.
     * Case insensative
     *
     * @param Enrise_String|string $needle
     * @param bool $beforeNeedle
     * @return Enrise_String
     */
    public function stristr($needle, $beforeNeedle = false)
    {
        if ($needle instanceof self) {
            $needle = $needle->getValue();
        }
        if (!is_scalar($needle)) {
            throw new InvalidArgumentException('Needle must be scalar!');
        }
        $beforeNeedle = (bool) $beforeNeedle;
        $value = stristr($this->_value, $needle, $beforeNeedle);
        if (!$value) {
            $value = '';
        }
        return $this->_create($value);
    }

    /**
     * Return filtered string representation of string
     *
     * @return string
     */
    public function render()
    {
        $data = (string) parent::render();
        foreach ($this->getFilters() as $filter) {
            $data = $filter->filter($data);
        }
        if (0 === mb_strlen($data) && $this->isTranslateEmpty()) {
            $data = $this->translateEmpty();
        }
        return $data;
    }

    /**
     * Set value and set length
     *
     * @param string $value
     * @return void
     */
    protected function _setValue($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Value must be a string!');
        }
        $this->_length = mb_strlen($value);
        $this->_value = mb_substr($value, 0, $this->_length, 'auto');
    }

    protected function _getCharSet()
    {
        return self::ALPHA_CHARS;
    }
}