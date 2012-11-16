<?php
abstract class Enrise_ValueAbstract
{
    const VALIDATOR = 'validator';
    const FILTER = 'filter';
    const DEFAULT_NAMESPACE = 'Enrise';
    const EMPTY_TRANSLATION_KEY = 'empty_value';

    protected $_length;
    protected $_value;

    protected $_filters = array();
    protected $_validators = array();
    protected $_messages = array();

    protected $_translations = true;
    protected $translateEmpty = true;
    protected $_reference = true;
    protected $_referenceAutoClone = false;
    protected $_disabledFilters = false;
    protected $_disabledValidators = false;

    /**
     *
     * @var Enrise_Array
     */
    protected static $_namespaces = null;

    public static function factory($value, $class = null, $namespace = self::DEFAULT_NAMESPACE)
    {
        if (null === $class) {
            $class = get_called_class();
        }
        $namespace = trim((string) $namespace, '_');
        if (!self::getNamespaces()->inArray($namespace, true)) {
            $namespace = self::DEFAULT_NAMESPACE;
        }
        $class = $namespace . '_' . end(explode('_', $class));
        return new $class($value);
    }

    /**
     * @return Enrise_Array
     */
    protected static function getNamespaces()
    {
        if (null === self::$_namespaces) {
            self::$_namespaces = new Enrise_Array(Zend_Loader_Autoloader::getInstance()->getRegisteredNamespaces());
            self::$_namespaces->trim('_');
        }
        return self::$_namespaces;
    }

    /**
     * Set a flag to indicate if this class should spawn new objects or should modify it's own internal value
     *
     * @param bool $flag
     * @return Enrise_ValueAbstract
     */
    public function setReference($flag)
    {
        if ($flag instanceof Enrise_Bool) {
            $flag = $flag();
        }
        $this->_reference = (bool) $flag;
        if (false === $this->_reference && $this->_referenceAutoClone) {
            return clone $this;
        }
        return $this;
    }

    /**
     * Return the flag indicating if the class should spawn new objects or should alter the internal value
     *
     * @return bool
     */
    public function getReference()
    {
        return $this->_reference;
    }

    /**
     * Flag if by setting the reference flag to false the returned item should be cloned if this flag is true
     *
     * @param bool $flag
     * @return Enrise_ValueAbstract
     */
    public function setReferenceAutoClone($flag)
    {
        if ($flag instanceof Enrise_Bool) {
            $flag = $flag();
        }
        $this->_referenceAutoClone = (bool) $flag;
        return $this;
    }

    /**
     * Return the reference auto clone flag
     *
     * @return bool
     */
    public function getReferenceAutoClone()
    {
        return $this->_referenceAutoClone;
    }

    public function setDisabledFilters($flag)
    {
        if ($flag instanceof Enrise_Bool) {
            $flag = $flag();
        }
        $this->_disabledFilters = (bool) $flag;
        return $this;
    }

    /**
     * Return the disabled filters flag
     *
     * @return bool
     */
    public function getDisabledFilters()
    {
        return $this->_disabledFilters;
    }

    public function setDisabledValidators($flag)
    {
        if ($flag instanceof Enrise_Bool) {
            $flag = $flag();
        }
        $this->_disabledValidators = (bool) $flag;
        return $this;
    }

    /**
     * Return the disabled filters flag
     *
     * @return bool
     */
    public function getDisabledValidators()
    {
        return $this->_disabledValidators;
    }

    /**
     * @return Enrise_Array
     */
    public function getMessages()
    {
        return new Enrise_Array($this->_messages);
        $data = new Enrise_Array($this->_messages);
        $data = $data->implode('<br />' . PHP_EOL);
        return $data;
    }

    /**
     * @return Enrise_ValueAbstract
     */
    public function clearFilters()
    {
        $this->_filters = array();
        return $this;
    }

    /**
     *
     * @param $data
     * @return Enrise_ValueAbstract
     */
    public function setFilters($data)
    {
        $this->clearFilters();
        $this->addFilters($data);
        return $this;
    }

    /**
     *
     * @param $data
     * @return Enrise_ValueAbstract
     */
    public function addFilters($data)
    {
        foreach ($data as $value) {
            $this->addFilter($value);
        }
        return $this;
    }

    /**
     *
     * @param $value
     * @return Enrise_ValueAbstract
     */
    public function addFilter(Zend_Filter_Interface $value)
    {
        return $this->addControl($value, self::FILTER);
    }

    /**
     * Check if atleast one filter has been set
     *
     * @return Enrise_Bool
     */
    public function hasFilters()
    {
        return new Enrise_Bool(0 < count($this->_filters));
    }

    /**
     * @return Enrise_Array
     */
    public function getFilters()
    {
        if ($this->_disabledFilters) {
            return new Enrise_Array(array());
        }
        return new Enrise_Array($this->_filters);
    }

    /**
     * @return Enrise_ValueAbstract
     */
    public function clearValidators()
    {
        $this->_validators = array();
        return $this;
    }

    /**
     *
     * @param $data
     * @return Enrise_ValueAbstract
     */
    public function setValidators($data)
    {
        $this->clearValidators();
        $this->addValidators($data);
        return $this;
    }

    /**
     *
     * @param $data
     * @return Enrise_ValueAbstract
     */
    public function addValidators($data)
    {
        foreach ($data as $value) {
            $this->addValidator($value);
        }
        return $this;
    }

    /**
     *
     * @param $value
     * @return Enrise_ValueAbstract
     */
    public function addValidator(Zend_Validate_Interface $value)
    {
        return $this->addControl($value, self::VALIDATOR);
    }

    /**
     * Check if atleast one validator has been set
     *
     * @return Enrise_Bool
     */
    public function hasValidators()
    {
        return new Enrise_Bool(0 < count($this->_validators));
    }

    /**
     * @return Enrise_Array
     */
    public function getValidators()
    {
        if ($this->_disabledValidators) {
            return new Enrise_Array(array());
        }
        return new Enrise_Array($this->_validators);
    }

    /**
     * @return Enrise_ValueAbstract
     */
    public function clearRules()
    {
        return $this->clearValidators();
    }

    /**
     * Check if atleast one validator has been set
     *
     * @return Enrise_Bool
     */
    public function hasRules()
    {
        return $this->hasValidators();
    }

    /**
     *
     * @param $data
     * @return Enrise_ValueAbstract
     */
    public function setRules($data)
    {
        return $this->setValidators($data);
    }

    /**
     *
     * @param $data
     * @return Enrise_ValueAbstract
     */
    public function addRules($data)
    {
        return $this->addValidators($data);
    }

    /**
     *
     * @param $value
     * @return Enrise_ValueAbstract
     */
    public function addRule($value)
    {
        return $this->addValidator($value);
    }

    /**
     * @return Enrise_Array
     */
    public function getRules()
    {
        return $this->getValidators();
    }

    /**
     *
     * @param $control
     * @param $type
     * @return Enrise_ValueAbstract
     */
    public function addControl($control, $type)
    {
        switch ($type) {
            case self::VALIDATOR:
                $this->_validators[] = $control;
                break;
            case self::FILTER:
                $this->_filters[] = $control;
                break;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        return (string) $this->_value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function __get($key)
    {
        //Make sure there is only 1 underscore
        $key = '_' . trim($key, '_');
        if (isset($this->{$key})) {
            return $this->{$key};
        }
        return null;
    }

    /**
     *
     * @param $key
     * @param $value
     * @throws LogicException
     */
    public function __set($key, $value)
    {
        throw new LogicException('Overloading is not allowed!');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
        	return $this->render();
        } catch (Exception $e) {
            if (Zend_Registry::isRegistered('Zend_Log')) {
                Zend_Registry::get('Zend_Log')->warn($e->getTraceAsString());
            }
            trigger_error($e->getTraceAsString(), E_USER_WARNING);
            return '';
        }
    }

    public function disableTranslation()
    {
        $this->_translations = false;
        return $this;
    }

    public function enableTranslation()
    {
        $this->_translations = true;
        return $this;
    }

    public function setTranslateEmpty($flag)
    {
        if ($flag instanceof Enrise_Bool) {
            $flag = $flag();
        }
        $this->translateEmpty = (bool) $flag;
        return $this;
    }

    public function isTranslateEmpty()
    {
        return $this->_translations && $this->translateEmpty;
    }

    public function translateEmpty()
    {
        return $this->translate(constant(get_called_class() . '::EMPTY_TRANSLATION_KEY'));
    }

    /**
     * Shortcut method for translations
     *
     * @param mixed $val
     * @return mixed
     */
    protected function translate($val)
    {
        if (!$this->_translations || !Zend_Registry::isRegistered('Zend_Translate')) {
            return $val;
        }
        return Zend_Registry::get('Zend_Translate')->translate($val);
    }

    /**
     * Check if the return value should be by overwriting current object or spawn new object
     *
     * @param mixed $data
     * @return Enrise_ValueAbstract
     */
    protected function _create($data)
    {
        if ($this->_reference) {
            $this->_setValue($data);
            return $this;
        }
        $class = get_called_class();
        return new $class($data);
    }

    /**
     *
     * @param mixed $value
     */
    abstract protected function _setValue($value);
}