<?php
class Enrise_Form extends Zend_Form
{
    public static function manager($name)
    {
        return new self(Glitch_Registry::getConfig()->get('forms')->{$name});
    }
}