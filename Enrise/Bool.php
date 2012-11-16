<?php
class Enrise_Bool extends Enrise_ValueAbstract
{
    public function __construct($data)
    {
        $this->_setValue($data);
    }

    /**
     * Helpfull for calling this objects in a if statement
     * Just use if ($var()) instead of if ($var)
     *
     * @return bool
     */
    public function __invoke()
    {
        return $this->_value;
    }

    /**
     * Return a string true/false or translated string based on internal value
     *
     * @return string
     */
    public function render()
    {
        $str = 'false';
        if (true === $this->getValue()) {
            $str = 'true';
        }
        /**
         * As translation adapters have different rules about interpreting keys we need to prefix the true/false values
         * Ex. INI files have reserved keywords like true, false, on and off.
         */
        $data = $this->_translate('bool_' . $str);
        if ($data === 'bool_' . $str) {
            //Strip of the prefix
            $data = str_replace('bool_', '', $data);
        }
        return (string) $data;
    }

    protected function _setValue($val)
    {
        if (!is_bool($val)) {
            throw new InvalidArgumentException('Boolean value is expected but not given!');
        }
        $this->_value = $val;
        $this->_length = (int) $val;
    }
}