<?php
class Enrise_Float extends Enrise_Int
{
    public function __invoke()
    {
        return $this->isPositive();
    }

    public function isNegative()
    {
        return 0 > $this->_value;
    }

    public function isPositive()
    {
        return 0 < $this->_value;
    }

    protected function _setValue($value)
    {
        if (!is_float($value) && !ctype_digit($value) && !is_integer($value)) {
            throw new InvalidArgumentException('Value must be a float!');
        }
        //@todo: is it safe to cast?
        $this->_value = (float) $value;
        $this->_length = strlen($value);
    }
}