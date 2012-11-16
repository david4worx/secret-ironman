<?php
class Enrise_Dom_Query_Result extends Zend_Dom_Query_Result
{
    /**
     * Proxy methods to DOMNodelist
     *
     * @param string $name
     * @param array $args
     */
    public function __call($name, $args)
    {
        if (method_exists($this->_nodeList, $name)) {
            return call_user_func_array(array($this->_nodeList, $name), $args);
        }
        throw new RuntimeException('Method ' . $name . ' does not exist!');
    }
}