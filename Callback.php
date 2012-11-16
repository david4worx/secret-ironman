<?php
/**
 * @todo: hmmm what do we make of this? And how to get it working?
 *
 */
class Enrise_Callback extends Enrise_ValueAbstract
{
    public function __construct($data)
    {
        if (!is_callable($data)) {
            throw new InvalidArgumentException('Provide a valid callback function!');
        }
        $this->_setValue($data);
    }

    public function __call($name, $arguments)
    {
        var_dump($name, $arguments);
        die(__FILE__ . '@' . __LINE__);
    }

    public static function __callStatic($name, $arguments)
    {
        var_dump($name, $arguments);
        die(__FILE__ . '@' . __LINE__);
    }

    public function render()
    {
        return __CLASS__;
    }

    protected function _setValue($data)
    {
        $this->_value = $data;
        $this->_length = 0;
    }
}