<?php
class Enrise_View_Helper_Query extends Zend_View_Helper_Abstract implements Countable
{
    /**
     *
     * @var Zend_Dom_Query_Result
     */
    protected $_result;

    protected $_parent;

    /**
     *
     * @var string
     */
    protected $_content;

    /**
     *
     * @var mixed
     */
    protected $_query;

    protected static $_hideClass = 'hidden';
    protected static $_showClass = '';

    public static function setHideClass($class)
    {
        self::$_hideClass = (string) $class;
    }

    public static function setShowClass($class)
    {
        self::$_showClass = (string) $class;
    }

    public function __construct($data = null)
    {
        $this->_result = $data;
        //Keep a copy for local reference
        $this->_parent = $data;
    }

    public function count()
    {
        return count($this->_result);
    }

    /*public function query($content, $query, $context = null)
    {
        if ($content instanceof self) {
            $content = $this->render();
        }
        if (is_object($content) && method_exists($content, '__toString')) {
            $content = $content->__toString();
        }
        if (!is_string($content)) {
            throw new InvalidArgumentException('Content must be a string!');
        }
        $a = new Zend_Dom_Query($content);
        $data = $a->query($query);
        return new self($data);
    }*/

    /**
     * Clear previous used results
     *
     * @return Enrise_View_Helper_Query
     */
    public function clear()
    {
        $this->_result = null;
        $this->_parent = null;
        return $this;
    }

    public function query($query, $content = null, $context = null)
    {
        if (null === $content || $content instanceof self) {
            $content = $this->render();
        }
        if (is_object($content) && method_exists($content, '__toString')) {
            $content = $content->__toString();
        }
        if (!is_string($content)) {
            throw new InvalidArgumentException('Content must be a string!');
        }
        $a = new Zend_Dom_Query($content);
        $data = $a->query($query);
        return new self($data);
    }

    public function queryXpath($query, $content = null, $context = null)
    {
        if (null === $content || $content instanceof self) {
            $content = $this->render();
        }
        if (is_object($content) && method_exists($content, '__toString')) {
            $content = $content->__toString();
        }
        if (!is_string($content)) {
            throw new InvalidArgumentException('Content must be a string!');
        }
        $a = new Zend_Dom_Query($content);
        $data = $a->queryXpath($query);
        return new self($data);
    }

    public function hide()
    {
        $this->_addClassAttr(self::$_hideClass);
        return $this;
    }

    public function show()
    {
        $this->_addClassAttr(self::$_showClass);
        return $this;
    }

    public function odd()
    {
        $data = array();
        foreach ($this->_result as $k => $item) {
            if ($k % 2) {
                $data[] = $item;
            }
        }
        if (0 < count($data)) {
            $this->_result = $data;
        }
        return $this;
    }

    public function even()
    {
        $data = array();
        foreach ($this->_result as $k => $item) {
            if (0 === $k % 2) {
                $data[] = $item;
            }
        }
        if (0 < count($data)) {
            $this->_result = $data;
        }
        return $this;
    }

    public function at()
    {
        $args = func_get_args();
        if (is_array(reset($args))) {
            $args = array_shift($args);
        }
        $args = array_filter($args, function($a) {
            if (is_string($a) && in_array(strtolower($a, array(':last-child', 'last-child', 'last')))) {
                return count($this->_result);
            }
            return Zend_Validate::is($a, 'Digits');
        });
        $data = array();
        //@todo: implement something sturdy here... Multiple items over which to traverse.
        foreach ($this->_result as $k => $item) {
            if (in_array($k, $args)) {
                $data[] = $item;
            }
        }
        if (0 < count($data)) {
            $this->_result = $data;
        }
        return $this;
    }

    public function firstChild()
    {
        return $this->at(0);
    }

    public function lastChild()
    {
        return $this->at(count($this->_result) - 1);
    }

    public function firstHalf()
    {
        $data = round(count($this->_result) / 2) - 1;
        $data = range(0, $data);
        return $this->at($data);
    }

    public function lastHalf()
    {
        $count = count($this->_result);
        $data = round($count / 2);
        $data = range($data, $count);
        return $this->at($data);
    }

    public function remove()
    {
        foreach ($this->_result as $item) {
            $item->parentNode->removeChild($item);
        }
        return $this;
    }

    public function __invoke()
    {
        return $this->toParent();
    }

    public function _()
    {
        return $this->toParent();
    }

    public function toParent()
    {
        $this->_result = $this->_parent;
        return $this;
    }

    public function css($styles)
    {
        if (is_scalar(func_get_arg(0)) && is_scalar(func_get_arg(1))) {
            $styles = array(func_get_arg(0) => func_get_arg(1));
        }
        if (is_array($styles)) {
            $styles = new Enrise_Array($styles);
        }
        if ($styles instanceof Enrise_Array) {
            $styles = $styles->implodeKey(': ', '; ');
        }
        $this->setAttr('style', $styles);
        return $this;
    }

    public function attr($get, $set = null)
    {
        if (2 === func_num_args()) {
            return $this->setAttr($get, $set);
        }
        return new Enrise_Array($this->getAttr($get));
    }

    public function getAttr($key)
    {
        $data = array();
        foreach ($this->_result as $item) {
            if ('value' === $key) {
                $data[] = $item->nodeValue;
            } else {
                $data[] = $item->getAttribute($key);
            }
        }
        return $data;
    }

    public function setAttribs($data)
    {
        if ($data instanceof Traversable || is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_scalar($k)) {
                    $this->setAttr($k, $v);
                }
            }
        }
        return $this;
    }

    public function setAttr($key, $value)
    {
        foreach ($this->_result as $item) {
            if ('value' === $key) {
                $item->nodeValue = $value;
            } else {
                $item->setAttribute($key, $value);
            }
        }
        return $this;
    }

    protected function _addClassAttr($class, DOMNode $item = null)
    {
        if (null !== $item) {
            $classes = explode(' ', $item->getAttribute('class'));
            $classes[] = $class;
            $item->setAttribute('class', trim(implode(' ', array_unique($classes))));
            return;
        }
        foreach ($this->_result as $item) {
            $this->_addClassAttr($class, $item);
        }
    }

    public function filter($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Provide a valid callback!');
        }
        return $this;
    }

    public function getValue()
    {
        $str = '';
        foreach ($this->_result as $item) {
            $str .= $item->nodeValue . PHP_EOL;
        }
        return $str;
    }

    public function render()
    {
        $res = $this->_result;
        if (!$res instanceof Zend_Dom_Query_Result && !$res instanceof self) {
            $this->_result = $res = $this->_parent;
        }
        $dom = new DOMDocument('1.0');
        foreach ($res as $item) {
            $dom->appendChild($dom->importNode($item, true));
        }
        $parent = $dom->getElementsByTagName('body')->item(0);
        $res = $dom;
        //$res = $res->getDocument();
        //$parent = $res->getElementsByTagName('body')->item(0);
        return trim(str_replace(array('<body>', '</body>'), '', (string) $res->saveHTML($parent)));
    }

    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            trigger_error($e->getTraceAsString(), E_USER_WARNING);
            return '';
        }
    }

    protected function __($res = null)
    {
        if (null !== $res) {
            $this->_result = $res;
        }
        return $this->_result;
    }
}