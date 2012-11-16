<?php
trait Enrise_Extendable
{
    protected $_methods = array();
    protected $_registers = array();

    public function __set($k, $v)
    {
        $this->_methods[$k] = $v;
        return $this;
    }

    public function extend($name, $function)
    {
        if (!is_callable($function)) {
            throw new InvalidArgumentException('No valid callback provided!');
        }
        if (array_key_exists($name, $this->_methods)) {
            $trace = $this->_registers[$name];
            throw new RuntimeException(sprintf('Method with name %s already exists. It is defined in %s(%d)', $name, $trace['file'], $trace['line']));
        }
        $this->_methods[$name] = $function;
        $e = new Exception('foo');
        $this->_registers[$name] = $e->getTrace()[0];
        return $this;
    }

    public function get($name)
    {
        if (!array_key_exists($name, $this->_methods)) {
            throw new RuntimeException(sprintf('No method with name %s registered!', $name));
        }
        return $this->_methods[$name];
    }

    public function __call($name, $args)
    {
        if (array_key_exists($name, $this->_methods)) {
            $args = array_merge(array($this), $args);
            return call_user_func_array($this->_methods[$name], $args);
        }
        throw new RuntimeException(sprintf('No method with name %s defined in %s', $name, get_called_class()));
    }
}