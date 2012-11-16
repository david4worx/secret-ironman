<?php
class Enrise_Test_RegexTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Enrise_Regex
     */
    protected $regex;

    protected function setUp()
    {
        $this->regex = new Enrise_Regex('foobar');
    }

    public function testAddRegex()
    {

        $t = $this->regex;
        $t = new Enrise_Regex();
        var_dump($t);die(__FILE__ . '@' . __LINE__);
        $t->addLookAhead('q=');
        $t->addRegex(new Enrise_Regex('bar-bat'));
        $t->addGroup('abc');
        var_dump($t);
        $z = new Enrise_Regex('def');
        $z->addGroup('-ghi');
        $z->addGroup('-jkl');
        //$t->addGroup($z);
        $t->addLookAhead('q=');
        var_dump((string) $t);
    }
}