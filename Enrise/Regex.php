<?php
class Enrise_Regex extends Enrise_String
{
    const RELATION_OR = 'or';
    const RELATION_AND = 'and';

    /**
     * String of valid patter modifiers
     *
     * @see http://nl3.php.net/manual/en/reference.pcre.pattern.modifiers.php
     * @var string
     */
    const PATTERN_MODIFIERS = 'imsxeADSUXJu';

    protected $modifiers = array();

    protected $name;

    public function __construct($value, $modifiers = null)
    {
        parent::__construct($value);
        if (null !== $modifiers) {
            $this->setModifiers($modifiers);
        }
    }

    public function addGroups($groups)
    {
        if (!$groups instanceof Traversable && !is_array($group)) {
            throw new InvalidArgumentException('Provide a traversable object or array!');
        }
        foreach ($groups as $group) {
            $this->addGroup($group);
        }
        return $this;
    }

    public function addGroup($regex)
    {
        if (!$regex instanceof self) {
            $regex = new self($regex);
        }
        $regex = $this->getValue() . '|(' . $regex->getValue() . ')';
        $this->_setValue($regex);
        return $this;
    }

    public function addRegex(self $regex, $relation = self::RELATION_OR)
    {
        switch ($relation) {
            case self::RELATION_AND:
                $regex = '(' . $this->getValue() . $regex->getValue() . ')';
                break;
            case self::RELATION_OR:
            default:
                $regex = '(' . $this->getValue() . ')|(' . $regex->getValue() . ')';
                break;
        }
        $this->_setValue($regex);
        return $this;
    }

    public function addLookAhead($value)
    {
        $regex = $this->getValue();
        $regex = '(' . $value . '?=(' . $regex . '))';
        $this->_setValue($regex);
        return $this;
    }

    public function setModifiers($value)
    {
        $this->clearModifiers();
        $this->addModifiers($value);
        return $this;
    }

    public function addModifier($value)
    {
        if (false === strpos(self::PATTERN_MODIFIERS, (string) $value)) {
            throw new InvalidArgumentException('Invalid pattern modifier given!');
        }
        $this->modifiers[] = $value;
        $this->modifiers = array_unique($this->modifiers);
        return $this;
    }

    public function addModifiers($data)
    {
        $data = array_filter((array) $data);
        foreach ($data as $value) {
            $this->addModifier($value);
        }
        return $this;
    }

    public function clearModifiers()
    {
        $this->modifiers = array();
        return $this;
    }

    public function removeModifier($value)
    {
        $search = array_search($value, $this->modifiers, true);
        if (false !== $search) {
            unset($this->modifiers[$search]);
        }
        return $this;
    }

    public function setCaseInsensitive()
    {
        $this->addModifier('i');
        return $this;
    }

    public function setCaseSensitive()
    {
        $this->removeModifier('i');
        return $this;
    }

    public function render()
    {
        $data = parent::render();
        if (0 < count($this->modifiers)) {
            $data .= '/' . implode($this->modifiers);
        }
        return $data;
    }

    public function test($value, $quote = true)
    {
        if (true === $quote) {
            $value = preg_quote($value);
        }
        return (bool) preg_match($this->getRegex(), $value);
    }

    public function exec($value, $quote = true)
    {
        if (true === $quote) {
            $value = preg_quote($value);
        }
        $matches = array();
        preg_match($this->getRegex(), $value, $matches);
        return new Enrise_Array($matches);
    }

    public function getRegex()
    {
        return '~' . $this->render() . '~';
    }

    /*protected function _setValue($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Value must be a string!');
        }
        parent::_setValue(implode(array('~', trim($value, '~'), '~')));
    }*/
}