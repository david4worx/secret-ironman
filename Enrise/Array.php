<?php
class Enrise_Array extends Enrise_ValueAbstract implements ArrayAccess, Iterator, Countable, Enrise_SharedInterface
{
    const EMPTY_TRANSLATION_KEY = 'empty_array';

    protected $domNummericNodeName = 'item';

    public function __construct($data = array())
    {
        $this->_setValue($data);
    }

    /**
     * When calling this object as a function return the inner value
     *
     * @return array
     */
    public function __invoke()
    {
        return $this->getValue();
    }

    /**
     * (non-PHPdoc)
     * @see Enrise_ValueAbstract::__get()
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->_value)) {
            return $this->_value[$key];
        }
        return parent::__get($key);
    }

    /**
     * Used when the array keys are numeric
     *
     * @param string $v
     * @return Enrise_Array
     */
    public function setDomNummericNodeName($v)
    {
        $this->domNummericNodeName = $v;
        return $this;
    }

    /**
     * Get the DOM string representation of numeric items
     *
     * @return string
     */
    public function getDomNummericNodeName()
    {
        return $this->domNummericNodeName;
    }

    /**
     * Returns the DOM representation of this array object
     *
     * @param string $rootNode
     * @return DOMDocument
     */
    public function toDom($rootNode = 'data')
    {
        $dom = new DOMDocument();
        $rootNode = $dom->createElement($rootNode);
        $dom->appendChild($rootNode);
        $this->arrayToXml($this, $rootNode);
        return $dom;
    }

    /**
     * Returns the XPath object of this array
     *
     * @return DOMXPath
     */
    public function toXpath()
    {
        return new DOMXPath(call_user_func_array(array(get_called_class(), 'toDom'), func_get_args()));
    }

    /**
     * Convert a traversable data piece into a DOM structure
     *
     * @param array|Traversable $data
     * @param DOMElement $node
     * @return Enrise_Array
     */
    protected function arrayToXml($data, DOMElement $node)
    {
        if (is_array($data) || $data instanceof Traversable) {
            foreach ($data as $k => $v) {
                $useId = null;
                if (ctype_digit($k) || is_int($k)) {
                    $useId = $k;
                    $k = $this->getDomNummericNodeName();
                }
                if (is_scalar($v)) {
                    if (class_exists('Zend_View')) {
                        $v = Zend_Layout::getMvcInstance()->getView()->escape($v);
                    }
                    $new = new DOMElement($k, $v);
                    $node->appendChild($new);
                    if (null !== $useId) {
                        $new->setAttribute('id', $useId);
                    }
                    continue;
                }
                $newNode = new DOMElement($k);
                $node->appendChild($newNode);
                $this->arrayToXml($v, $newNode);
            }
        }
        return $this;
    }

    /**
     * Returns XML representation of this array object
     *
     * @return string
     */
    public function toXml()
    {
        return call_user_func_array(array(get_called_class(), 'toDom'), func_get_args())->saveXML();
    }

    /**
     * Returns JSON representation of this array
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->_value);
    }

    /**
     * Merge 2 data structures into one
     *
     * @param Enrise_Array $data
     * @return Enrise_Array
     */
    public function merge($data = array(), $useFilters = false, $useValidators = false)
    {
        if (empty($data)) {
            //Makes no sense but we have an interface to follow
            return $this;
        }
        if (!$data instanceof self) {
            $data = new self($data);
        }
        if (true === $useFilters) {
            $this->addFilters($data->getFilters());
        }
        if (true === $useValidators) {
            $this->addValidators($data->getValidators());
        }
        $data = array_merge($this->_value, $data->getValue());
        $tmp = array();
        foreach ($data as $k => $val) {
            if (true === $useFilters) {
                $val = $this->filter($val);
            }
            if ($useValidators && !$this->isValid($val)) {
                //Skip the invalid item
                continue;
            }
            //Append it to the new array
            $tmp[$k] = $val;
        }
        return $this->_create($tmp);
    }

    /**
     * Change the key case of the array keys
     *
     * @param mixed $case
     * @return Enrise_Array
     */
    public function changeKeyCase($case = CASE_LOWER)
    {
        if (is_object($case) && method_exists($case, '__toString')) {
            $case = $case->__toString();
        }
        switch ($case) {
            case 'LOWER':
            case 'lower':
                $case = CASE_LOWER;
                break;
            case 'UPPER':
            case 'upper':
                $case = CASE_UPPER;
                break;
        }
        if (!in_array($case, array(CASE_LOWER, CASE_UPPER), true)) {
            throw new InvalidArgumentException('Invalid case specifier given!');
        }
        return $this->_create(array_change_key_case($this->_value, $case));
    }

    /**
     * Shorten the array by taking of the last element and place it in $var
     * Returns the new array
     *
     * @param mixed $var
     * @return Enrise_Array
     */
    public function pop(&$var)
    {
        $var = array_pop($this->_value);
        if (is_string($var)) {
            $var = new Enrise_String($var);
        }
        if (is_array($var)) {
            $var = new Enrise_Array($var);
        }
        return $this->_create($this->_value);
    }

    /**
     * Flip the array keys with their values
     *
     * @return Enrise_Array
     */
    public function flip()
    {
        return $this->_create(array_flip($this->getValue()));
    }

    /**
     * Reverse the array order
     *
     * @param bool $preserveKeys
     * @return Enrise_Array
     */
    public function reverse($preserveKeys = false)
    {
        return $this->_create(array_reverse($this->_value, $preserveKeys));
    }

    /**
     * Combine the given data as values and use the internal data as keys
     *
     * @param Enrise_Array|array $data
     * @return Enrise_Array
     */
    public function combine($data)
    {
        if ($data instanceof self) {
            $data = $data->getValue();
        }
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data should be an array or an instance of ' . get_class());
        }
        $val = $this->getValue();
        if (count($val) !== count($data)) {
            throw new LengthException('Unequal ammount of items detected, unable to merge!');
        }
        return $this->_create(array_combine($this->getValue(), $data));
    }

    /**
     * Append an item to this array
     *
     * @param mixed $data
     * @return Enrise_Array
     */
    public function append()
    {
        foreach (func_get_args() as $arg) {
            /*if (!$this->isValid($arg)) {
                throw new InvalidArgumentException('Data did not validate!');
            }*/
            $this->_value[] = $arg;
        }
        //Recount
        $this->_setValue($this->_value);
        return $this;
    }

    /**
     * Insert an item into the array at the given position
     *
     * @param mixed $item
     * @param numeric $pos
     * @param string $fallbackPlacement
     * @return Enrise_Array
     */
    public function insert($item, $pos = null, $fallbackPlacement = 'append')
    {
        //Skip recursion, gives weird results..
        if ($item === $this) {
            return $this;
        }
        if (null === $pos) {
            $pos = $this->length + 1;
        }
        if ($pos > $this->length) {
            switch ($fallbackPlacement) {
                case 'prepend':
                    $this->prepend($item);
                    break;
                case 'append':
                default:
                    $this->append($item);
                    break;
            }
            return $this;
        }
        $split = array_slice($this->_value, 0, $pos);
        $rest  = array_slice($this->_value, $pos);
        if (!is_array($item)) {
            $item = array($item);
        }
        $this->_setValue(array_merge($split, $item, $rest));
        return $this;
    }

    /**
     * Prepend an item to this array
     *
     * @param mixed $data
     * @return Enrise_Array
     */
    public function prepend()
    {
        $args = func_get_args();
        $args = array_reverse($args);
        foreach ($args as $arg) {
            array_unshift($this->_value, $arg);
        }
        //Recount
        $this->_setValue($this->_value);
        return $this;
    }

    /**
     * Check if the given data is valid according to the validators registered
     *
     * @param mixed $data
     * @return bool
     */
    public function isValid($data = null, $breakChainOnFailure = false)
    {
        if (0 === func_num_args()) {
            $data = $this->getValue();
        }
        $valid = true;
        foreach ($this->_validators as $validator) {
            if (!$validator->isValid($data)) {
                $this->_messages[] = $validator->getMessages();
                $valid = false;
                if (true === $breakChainOnFailure) {
                    break;
                }
            }
        }
        return $valid;
    }

    /**
     * Filter out entries by running it through the validators
     *
     * @return Enrise_Array
     */
    public function filterByValidate()
    {
        $data = array();
        foreach ($this->_value as $k => $v) {
            if ($v instanceof self) {
                $data[$k] = $v->filterByValidate();
                //@todo hmmm, no clue, YET!
                //$v->validate();
                continue;
            }
            if ($this->isValid($v)) {
                $data[$k] = $v;
            }
        }
        return $this->_create($data);
    }

    /**
     * Computes the intersection of arrays
     *
     * @see http://nl2.php.net/array_intersect
     * @return Enrise_Array
     */
    public function intersect()
    {
        return $this->_callback('array_intersect', array_merge(array($this->getValue()), func_get_args()));
    }

    /**
     * Computes the intersection of arrays using keys for comparison
     *
     * @see http://nl2.php.net/array_intersect_key
     * @return Enrise_Array
     */
    public function intersectKey()
    {
        return $this->_callback('array_intersect_key', array_merge(array($this->getValue()), func_get_args()));
    }

    /**
     * Computes the intersection of arrays with additional index check
     *
     * @see http://nl2.php.net/array_intersect_assoc
     * @return Enrise_Array
     */
    public function intersectAssoc()
    {
        return $this->_callback('array_intersect_assoc', array_merge(array($this->getValue()), func_get_args()));
    }

    /**
     * Computes the difference of arrays
     *
     * @see http://nl2.php.net/array_diff
     * @return Enrise_Array
     */
    public function diff()
    {
        return $this->_callback('array_diff', array_merge(array($this->getValue()), func_get_args()));
    }

    /**
     * Computes the difference of arrays using keys for comparison
     *
     * @see http://nl2.php.net/array_diff_key
     * @return Enrise_Array
     */
    public function diffKey()
    {
        return $this->_callback('array_diff_key', array_merge(array($this->getValue()), func_get_args()));
    }

    /**
     * Computes the difference of arrays with additional index check
     *
     * @see http://nl2.php.net/array_diff_assoc
     * @return Enrise_Array
     */
    public function diffAssoc()
    {
        return $this->_callback('array_diff_assoc', array_merge(array($this->getValue()), func_get_args()));
    }

    /**
     * Shuffle an array
     *
     * @return Enrise_Array
     */
    public function shuffle()
    {
        shuffle($this->_value);
        return $this;
    }

    /**
     * Return a random ammount of items from this array
     *
     * @param int $min
     * @param int $max
     * @return Enrise_Array
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
        if (null !== $max && !is_numeric($max)) {
            throw new InvalidArgumentException('Param $max must be numeric!');
        }
        if ($min > $this->length) {
            throw new OutOfBoundsException('Given $min is greater then length of this array!');
        }
        if (null === $max || $max > $this->length) {
            $max = $min;
        }
        $ammount = mt_rand($min, $max);
        return $this->_create(array_intersect_key($this->_value, array_flip((array) array_rand($this->shuffle()->_value, $ammount))));
    }

    /**
     * Creates a range of values
     *
     * @param mixed $start
     * @param mixed $limit
     * @param int $step
     * @return Enrise_Array
     */
    public function range($start, $limit, $step = 1)
    {
        return $this->_create(range($start, $limit, $step));
    }

    /**
     * Pad array to the specified length with a value
     *
     * @see http://nl2.php.net/array_pad
     * @param mixed $size
     * @param mixed $value
     * @return Enrise_Array
     */
    public function pad($size, $value)
    {
        if (is_object($size) && method_exists($size, '__toString')) {
            $size = $size->__toString();
        }
        if (!is_numeric($size)) {
            throw new InvalidArgumentException('Param $ammount must be scalar!');
        }
        $size = (float) $size;
        return $this->_create(array_pad($this->_value, $size, $value));
    }

    /**
     * Searches the array for a given value and returns the corresponding key if successful
     *
     * Returns an Enrise_String as keys can be all scalar types
     *
     * @see http://nl2.php.net/array_search
     * @param mixed $value
     * @param bool $strict
     * @return Enrise_Bool|Enrise_String
     */
    public function search($value, $strict = true)
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            $value = $value->__toString();
        }
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Param $value must be scalar!');
        }
        $strict = (bool) $strict;
        $value = array_search($value, $this->_value, $strict);
        if (false === $value) {
            return new Enrise_Bool($value);
        }
        return new Enrise_String((string) $value);
    }

    /**
     * Check if a given $value exists in this array
     *
     * @see http://nl2.php.net/in_array
     * @param mixed $value
     * @param bool $strict
     * @return Enrise_Bool
     */
    public function inArray($value, $strict = true)
    {
        $data = $this->search($value, $strict);
        return new Enrise_Bool($data instanceof Enrise_String);
    }

    /**
     * Return all the keys or a subset of the keys of an array
     *
     * @see http://nl2.php.net/array_keys
     * @param unknown_type $search
     * @param unknown_type $strict
     */
    public function keys($search = null, $strict = true)
    {
        if (is_object($search) && method_exists($search, '__toString')) {
            $search = $search->__toString();
        }
        if (!is_scalar($search)) {
            throw new InvalidArgumentException('Param $search must be scalar!');
        }
        $strict = (bool) $strict;
        if (!empty($search)) {
            $data = array_keys($this->_value, $search, $strict);
        } else {
            $data = array_keys($this->_value);
        }
        return new Enrise_Array($data);
    }

    /**
     * Sort an array by values using a user-defined comparison function
     *
     * @see http://nl2.php.net/usort
     * @param mixed $callback
     * @return Enrise_Array
     */
    public function usort($callback)
    {
        return $this->_callbackSort(__FUNCTION__, $callback);
    }

    /**
     * Sort an array with a user-defined comparison function and maintain index association
     *
     * @see http://nl2.php.net/uasort
     * @param mixed $callback
     * @return Enrise_Array
     */
    public function uasort($callback)
    {
        return $this->_callbackSort(__FUNCTION__, $callback);
    }

    /**
     * Sort an array by keys using a user-defined comparison function
     *
     * @see http://nl2.php.net/uksort
     * @param mixed $callback
     * @return Enrise_Array
     */
    public function uksort($callback)
    {
        return $this->_callbackSort(__FUNCTION__, $callback);
    }

    /**
     * Sort an array using a "natural order" algorithm
     *
     * @see http://nl2.php.net/natsort
     * @param mixed $callback
     * @return Enrise_Array
     */
    public function natsort()
    {
        return $this->_sort(__FUNCTION__);
    }

    /**
     * Sort an array using a case insensitive "natural order" algorithm
     *
     * @see http://nl2.php.net/natcasesort
     * @param mixed $callback
     * @return Enrise_Array
     */
    public function natcasesort()
    {
        return $this->_sort(__FUNCTION__);
    }

    /**
     * Sort an array
     *
     * @see http://nl2.php.net/sort
     * @param int $flag
     * @return Enrise_Array
     */
    public function sort($flag = SORT_REGULAR)
    {
        return $this->_sort(__FUNCTION__, $flag);
    }

    /**
     * Reverse sort an array
     *
     * @see http://nl2.php.net/rsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function rsort($flag = SORT_REGULAR)
    {
        return $this->_sort(__FUNCTION__, $flag);
    }

    /**
     * Reverse sort an array
     *
     * @see http://nl2.php.net/rsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function reverseSort($flag = SORT_REGULAR)
    {
        return $this->rsort($flag);
    }

    /**
     * Sort an array and maintain index association
     *
     * @see http://nl2.php.net/asort
     * @param int $flag
     * @return Enrise_Array
     */
    public function asort($flag = SORT_REGULAR)
    {
        return $this->_sort(__FUNCTION__, $flag);
    }

    /**
     * Sort an array and maintain index association
     *
     * @see http://nl2.php.net/asort
     * @param int $flag
     * @return Enrise_Array
     */
    public function arraySort($flag = SORT_REGULAR)
    {
        return $this->asort($flag);
    }

    /**
     * Sort an array in reverse order and maintain index association
     *
     * @see http://nl2.php.net/arsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function arsort($flag = SORT_REGULAR)
    {
        return $this->_sort(__FUNCTION__, $flag);
    }

    /**
     * Sort an array in reverse order and maintain index association
     *
     * @see http://nl2.php.net/arsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function arrayReverseSort($flag = SORT_REGULAR)
    {
        return $this->arsort($flag);
    }

    /**
     * Sort an array by key
     *
     * @see http://nl2.php.net/arsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function ksort($flag = SORT_REGULAR)
    {
        return $this->_sort(__FUNCTION__, $flag);
    }

    /**
     * Sort an array by key
     *
     * @see http://nl2.php.net/arsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function keySort($flag = SORT_REGULAR)
    {
        return $this->ksort($flag);
    }

    /**
     * Sort an array by key in reverse order
     *
     * @see http://nl2.php.net/arsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function krsort($flag = SORT_REGULAR)
    {
        return $this->_sort(__FUNCTION__, $flag);
    }

    /**
     * Sort an array by key in reverse order
     *
     * @see http://nl2.php.net/arsort
     * @param int $flag
     * @return Enrise_Array
     */
    public function keyReverseSort($flag = SORT_REGULAR)
    {
        return $this->krsort($flag);
    }

    /**
     * Check if given param is callable and throw exception if it's not
     *
     * @param mixed $callback
     * @throws InvalidArgumentException
     */
    protected function _isCallable($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callback given!');
        }
    }

    /**
     * Callback for intersection/diff functions
     *
     * @param mixed $callback
     * @param array $data
     * @return Enrise_Array
     */
    protected function _callback($callback, array $data)
    {
        $tmp = array();
        foreach ($data as $v) {
            if (is_array($v) || $v instanceof self) {
                if ($v instanceof self) {
                    $v = $arg->getValue();
                }
                $tmp[] = $v;
            }
        }
        return $this->_create(call_user_func_array($callback, $tmp));
    }

    /**
     * Function for callback sort functions
     *
     * @param string $function
     * @param mixed $callback
     * @return Enrise_Array
     */
    protected function _callbackSort($function, $callback)
    {
        $this->_isCallable($callback);
        $data = $this->_value;
        $function($data, $callback);
        return $this->_create($data);
    }

    /**
     * Shortcut sort function with $flag check
     *
     * @param string $function
     * @param int $flag
     * @throws InvalidArgumentException
     * @return Enrise_Array
     */
    protected function _sort($function, $flag = SORT_REGULAR)
    {
        if (null !== $flag && !in_array($flag, array(SORT_REGULAR, SORT_NUMERIC, SORT_STRING, SORT_LOCALE_STRING), true)) {
            throw new InvalidArgumentException('Invalid sort flag given!');
        }
        $data = $this->_value;
        $function($data);
        return $this->_create($data);
    }

    /**
     * Filter out empty values by giving a callback or by default
     *
     * @param $callback
     * @return Enrise_Array
     */
    public function filterEmpty($callback = null)
    {
        if (null !== $callback && !is_callable($callback)) {
            throw new InvalidArgumentException('Callback is invalid!');
        }
        if (null === $callback) {
            $callback = function($arg) {
                if (is_scalar($arg)) {
                    return strlen($arg);
                }
                if (is_object($arg) && $arg instanceof Enrise_ValueAbstract) {
                    return $arg->length;
                }
                if (is_array($arg) || (is_object($arg) && $arg instanceof Countable)) {
                    return 0 < count($arg);
                }
                return !empty($arg);
            };
        }
        return $this->_create(array_filter((array) $this->getValue(), $callback));
    }

    /**
     * Filter data according to the filters registered
     *
     * @param mixed $data
     * @return mixed
     */
    public function filter($data)
    {
        foreach ($this->_filters as $filter) {
            $data = $filter->filter($data);
        }
        return $data;
    }

    /**
     * Trim chars of every entry in this object recursively
     *
     * @param string $charlist
     * @return Enrise_Array
     */
    public function trim($charlist = null)
    {
        if ($charlist instanceof self) {
            $charlist = $charlist->implode('');
        }
        $charlist = (string) $charlist;
        foreach ($this->_value as &$data) {
            if ($data instanceof self) {
                $data->trim($charlist);
                continue;
            }
            if (is_string($data)) {
                if (0 === strlen($charlist)) {
                    $data = trim($data);
                } else {
                    $data = trim($data, $charlist);
                }
            }
        }
        return $this->_create($this->_value);
    }

    /**
     * Implode this array into a string
     *
     * @param mixed $glue
     * @return string
     */
    public function implode($glue)
    {
        if (is_object($glue) && method_exists($glue, '__toString')) {
            $glue = $glue->__toString();
        }
        if (!is_scalar($glue)) {
            throw new InvalidArgumentException('Glue must be scalar or an instanceof Enrise_String!');
        }
        $parts = array();
        foreach ($this->_value as $k => $part) {
            if (is_array($part)) {
                $part = new self($part);
            }
            if ($part instanceof self) {
                $part = $part->implode($glue);
            }
            $part = $this->filter($part);
            if ($this->isValid($part)) {
                if ($part instanceof Enrise_ValueAbstract) {
                    $part = $part->render();
                }
                //$parts[] = '<!-- ' . $k . ' -->' . $part;
                $parts[] = $part;
            }
        }
        $parts = array_map(array($this, 'translate'), $parts);
        if (0 === count($parts) && $this->isTranslateEmpty()) {
            $parts = array($this->translateEmpty());
        }
        return trim(implode($glue, $parts));
    }

    public function implodeKey($glue1, $glue2)
    {
        if (is_object($glue1) && method_exists($glue1, '__toString')) {
            $glue1 = $glue1->__toString();
        }
        if (!is_scalar($glue1)) {
            throw new InvalidArgumentException('Glue must be scalar or an instanceof Enrise_String!');
        }
        if (is_object($glue2) && method_exists($glue2, '__toString')) {
            $glue2 = $glue2->__toString();
        }
        if (!is_scalar($glue2)) {
            throw new InvalidArgumentException('Glue must be scalar or an instanceof Enrise_String!');
        }
        $parts = '';
        foreach ($this->_value as $k => $part) {
            if (is_array($part)) {
                $part = new self($part);
            }
            if ($part instanceof self) {
                $part = $part->implodeKey($glue1, $glue2);
            }
            $part = $this->filter($part);
            if ($this->isValid($part)) {
                if ($part instanceof Enrise_ValueAbstract) {
                    $part = $part->render();
                }
                $parts .= $this->translate($k) . $glue1 . $this->translate($part) . $glue2;
            }
        }
        if (0 === mb_strlen($parts) && $this->isTranslateEmpty()) {
            $parts = $this->translateEmpty();
        }
        return trim($parts);
    }

    /**
     * Return all params in this object as a http query string
     *
     * @return Enrise_String
     */
    public function httpQuery()
    {
        return new Enrise_String('?' . http_build_query($this->getValue()));
    }

    /**
     * Make all entries unique in this array.
     * If $recursive is true it will also make all entries unique in all child arrays
     *
     * @param Enrise_Bool|bool $recursive
     * @return Enrise_Array
     */
    public function unique($recursive = false)
    {
        if ($recursive instanceof Enrise_Bool) {
            $recursive = $recursive();
        }
        if (false === $recursive) {
            return $this->_create(array_unique($this->_value));
        }
        $data = array();
        foreach ($this->_value as $val) {
            if ($val instanceof self) {
                if (true === $recursive) {
                    $val = $val->unique($recursive);
                } else {
                    $val = $this->_value;
                }
            }
            if (is_array($val)) {
                $data = array_merge($data, array_unique($val));
            } else if (!in_array($val, $data, true)) {
                $data[] = $val;
            }
        }
        return $this->_create($data);
    }

    /**
     * Returns string representation of object
     *
     * @return string
     */
    public function render()
    {
        return $this->implode(' ');
    }

    /**
     * Return the length of the array
     *
     * @return int
     */
    public function count()
    {
        return $this->_length;
    }

    /**
     * Return current item
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->_value);
    }

    /**
     * Return the current key
     *
     * @return scalar
     */
    public function key()
    {
        return key($this->_value);
    }

    /**
     * Set next item
     *
     * @return void
     */
    public function next()
    {
        next($this->_value);
    }

    /**
     * Rewind the internal array
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->_value);
    }

    /**
     * Check if current element is still valid in travers terms
     *
     * @return bool
     */
    public function valid()
    {
        return (bool) $this->current();
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_value[] = $value;
        } else {
            $this->_value[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->_value[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_value[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_value[$offset]) ? $this->_value[$offset] : null;
    }

    /**
     *
     * @param Callable $callback
     * @return Enrise_Array
     */
    public function map($callback)
    {
        return $this->_create(array_map($callback, $this->_value));
    }

    /**
     *
     * @param Callable $callback
     * @return Enrise_Array
     */
    public function walk($callback)
    {
        array_walk($this->_value, $callback);
        return $this->_create($this->_value);
    }

    /**
     * Set value and set length
     *
     * @param array|Traversable $value
     * @return void
     */
    protected function _setValue($value)
    {
        if (!is_array($value) && !$value instanceof self) {
            throw new InvalidArgumentException('Data must be traversable!');
        }
        $this->_value = $value;
        $this->_length = count($value);
    }
}