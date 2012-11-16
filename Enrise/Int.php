<?php
class Enrise_Int extends Enrise_ValueAbstract implements Countable, Enrise_SharedInterface
{
    const EMPTY_TRANSLATION_KEY = 'empty_digit';

    public static function make($value = null)
    {
        $org = $value;
        if (null === $value) {
            $value = 0;
        }
        $class = 'Int';
        if (is_float($value)) {
            $class = 'Float';
        }
        $class = 'Enrise_' . $class;
        $data = new $class($value);
        if (null === $org) {
            $data = $data->random();
        }
        return $data;
    }

    public function __construct($value)
    {
        $this->_setValue($value);
    }

    public function count()
    {
        return $this->_value;
    }

    public function render()
    {
        $data = parent::render();
        //Be carefull with whats empty here, might have to check negative floats..
        if ('' === $data &&  $this->isTranslateEmpty()) {
            $data = $this->translateEmpty();
        }
        return $data;
    }

    public function search($value)
    {
        $data = new Enrise_String((string) $this->_value);
        return $data->search((string) $value);
        return new Enrise_Bool(false === strpos($this->_value, $value));
    }

    public function random($min = 1, $max = null)
    {
        if (0 === func_num_args()) {
            $data = mt_rand();
            return $this->_create($data);
        }
        if (is_object($min) && method_exists($min, '__toString')) {
            $min = $min->__toString();
        }
        if (is_object($min) && method_exists($max, '__toString')) {
            $max = $max->__toString();
        }
        if (!is_numeric($min)) {
            throw new InvalidArgumentException('Param $min must be numeric!');
        }
        if (!is_numeric($max)) {
            throw new InvalidArgumentException('Param $max must be numeric!');
        }
        return $this->_create(mt_rand($min, $max));
    }

    public function reverse()
    {
        $data = $this->_value;
        $type = gettype($data);
        $data = new Enrise_String((string) $data);
        $data = $data->reverse()->getValue();
        settype($data, $type);
        if ('integer' === $type) {
            return $this->_create($data);
        }
        return new Enrise_Float($data);
    }

    public function shuffle()
    {
        $data = str_split($this->_value, 1);
        shuffle($data);
        $float = (float) implode($data);
        $int = (int) $float;
        if (is_integer($int)) {
            //Hmmm reference and different objects..
            return $this->_create($int);
        }
        //Hmmm reference and different objects..
        $float = new Enrise_Float($float);
        $float->setReference($this->getReference());
        return $float;
    }

    public function modulo($times)
    {
        if ($times instanceof Enrise_ValueAbstract) {
            $times = $times->render();
        } else if (is_object($times) && method_exists($times, '__toString')) {
            $times = $times->__toString();
        }
        if (!Zend_Validate::is($times, 'Digits')) {
            throw new InvalidArgumentException('Provide a numeric value for ' . __FUNCTION__ . '!');
        }
        return $this->_create($this->_value % $times);
    }

    public function isEven()
    {
        return new Enrise_Bool((bool) $this->_value % 2);
    }

    public function isOdd()
    {
        return new Enrise_Bool(!$this->isEven()->getValue());
    }

    public function multiply($times)
    {
        if ($times instanceof Enrise_ValueAbstract) {
            $times = $times->render();
        } else if (is_object($times) && method_exists($times, '__toString')) {
            $times = $times->__toString();
        }
        if (!Zend_Validate::is($times, 'Digits')) {
            throw new InvalidArgumentException('Provide a numeric value for ' . __FUNCTION__ . '!');
        }
        return $this->_create($this->_value * $times);
    }

    public function devide($times)
    {
        if ($times instanceof Enrise_ValueAbstract) {
            $times = $times->render();
        } else if (is_object($times) && method_exists($times, '__toString')) {
            $times = $times->__toString();
        }
        if (!Zend_Validate::is($times, 'Digits')) {
            throw new InvalidArgumentException('Provide a numeric value for ' . __FUNCTION__ . '!');
        }
        return $this->_create($this->_value / $times);
    }

    public function sub()
    {
        $val = $this->_value;
        foreach (func_get_args() as $arg) {
            if ($arg instanceof Enrise_ValueAbstract) {
                $arg = $arg->render();
            }
            if (Zend_Validate::is($arg, 'Digits')) {
                $val -= $arg;
            }
        }
        return $this->_create($val);
    }

    public function add()
    {
        $val = $this->_value;
        foreach (func_get_args() as $arg) {
            if ($arg instanceof Enrise_ValueAbstract) {
                $arg = $arg->render();
            }
            if (Zend_Validate::is($arg, 'Digits')) {
                $val += $arg;
            }
        }
        return $this->_create($val);
    }

    public function merge()
    {
        foreach (func_get_args() as $arg) {
            $this->add($arg);
        }
        return $this;
    }

    protected function _setValue($value)
    {
        if (!is_integer($value)) {
            throw new InvalidArgumentException('Value must be a integer!');
        }
        $this->_value = $value;
        $this->_length = strlen($value);
    }
}