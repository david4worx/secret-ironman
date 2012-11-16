<?php
class Enrise_Test_ArrayTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Enrise_Array
     */
    protected $array;

    protected function setUp()
    {
        $this->array = new Enrise_Array(array('foo' => 'bar'));
    }

    public function testCountObject()
    {
        $this->assertCount(1, $this->array);
    }

    public function testAddFilter()
    {
        $filter = new Zend_Filter_StringToUpper();
        $this->array->addFilter($filter);
        $this->assertContains($filter, $this->array->getFilters(), true);
    }

    public function testDomOfObject()
    {
        $this->assertInstanceOf('DOMDocument', $this->array->toDom());
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
<data><foo>bar</foo></data>', $this->array->toXml());
        $this->assertInstanceOf('DOMXPath', $this->array->toXpath());
    }

    public function testJsonOfObject()
    {
        $this->assertEquals('{"foo":"bar"}', $this->array->toJson());
    }

    public function testMergeOfArray()
    {
        $arr = array('baz' => 'bat');
        $this->assertCount(2, $this->array->merge($arr));
    }

    public function testMergeOfEnriseArray()
    {
        $arr = new Enrise_Array(array('baz' => 'bat'));
        $this->assertCount(2, $this->array->merge($arr));
    }

    public function testMergeOfEnriseArrayWithFilter()
    {
        $filter = new Zend_Filter_StringToUpper();
        $arr = new Enrise_Array(array('baz' => 'bat'));
        $arr->addFilter($filter);
        $this->array->merge($arr, true);
        $this->assertCount(2, $this->array);
        //Watch the first items being lowercase!
        $this->assertEquals(array('foo' => 'BAR', 'baz' => 'BAT'), $this->array->getValue());
    }

    public function testMergeOfEnriseArrayWithValidator()
    {
        $validator = new Zend_Validate_InArray(
            array(
                'haystack' => array('bat'),
                'strict' => true,
            )
        );
        $arr = new Enrise_Array(array('baz' => 'bat'));
        $arr->addValidator($validator);
        $this->array->merge($arr, false, true);
        $this->assertCount(1, $this->array);
        //The first items are no longer here
        $this->assertEquals(array('baz' => 'bat'), $this->array->getValue());
    }

    public function testMergeOfEnriseArrayWithFilterAndValidator()
    {
        $filter = new Zend_Filter_StringToUpper();
        $validator = new Zend_Validate_InArray(
            array(
                'haystack' => array('baz' => 'bat'),
                'strict' => true,
            )
        );
        $arr = new Enrise_Array(array('baz' => 'bat'));
        $arr->addFilter($filter);
        $arr->addValidator($validator);
        $this->array->merge($arr, true, true);
        $this->assertCount(0, $this->array);
        //Not a single item should have passed the merge as the filter and validator are lowercase vs uppercase
        $this->assertEquals(array(), $this->array->getValue());
    }

    public function testChangeKeyCase()
    {
        $this->array->changeKeyCase(CASE_UPPER);
        $this->assertEquals(array('FOO' => 'bar'), $this->array->getValue());

        $this->array->changeKeyCase(CASE_LOWER);
        $this->assertEquals(array('foo' => 'bar'), $this->array->getValue());
    }

    public function testArrayPop()
    {
        $arr = new Enrise_Array(array('foo', 'bar', 'baz'));
        $t = null;
        $arr->pop($t);
        $this->assertEquals('baz', $t);
        $this->assertCount(2, $arr);
    }

    public function testArrayFlip()
    {
        $res = new Enrise_Array(array('bar' => 'foo'));
        $this->assertEquals($res, $this->array->flip());
    }

    public function testArrayReverse()
    {
        $tst = array('foo', 'bar', 'baz');
        $arr = new Enrise_Array($tst);
        $this->assertEquals(array_reverse($tst), $arr->reverse()->getValue());
    }

    public function testArrayCombine()
    {
        $arr = array('baz' => 'bat');
        $this->assertEquals(array('bar' => 'bat'), $this->array->combine($arr)->getValue());
    }

    /**
     * @expectedException LengthException
     */
    public function testArrayCombineWithUnequalNrOfItemsThrowsException()
    {
        $arr = array('baz', 'bat');
        $this->array->combine($arr);
    }

    public function testArrayAppend()
    {
        $this->assertCount(2, $this->array->append('foo'));
    }

    public function testArraySearch()
    {
        $pos = $this->array->search('bar');
        $this->assertInstanceOf('Enrise_String', $pos);
        $this->assertEquals('foo', $pos->getValue());
    }

    public function testArrayInsertDefaultBehaviour()
    {
        $this->array->insert('baz');
        $this->assertContains('baz', $this->array->getValue());
        //Foo is the first key and we inserted a numerical item so index starts from 0
        $this->assertEquals(0, $this->array->search('baz')->getValue());
    }

    public function testArrayInsertWithPos()
    {
        $this->array->insert('baz', 0);
        $this->assertContains('baz', $this->array->getValue());
        $this->assertEquals(array(0 => 'baz', 'foo' => 'bar'), $this->array->getValue());
    }

    public function testArrayInsertWithToHighPosAndFallbackToAppend()
    {
        $this->array->insert('baz', 5);
        $this->assertContains('baz', $this->array->getValue());
        $this->assertEquals(array('foo' => 'bar', 0 => 'baz'), $this->array->getValue());
    }

    public function testArrayInsertWithToHighPosAndFallbackToPrepend()
    {
        $this->array->insert('baz', 5, 'prepend');
        $this->assertContains('baz', $this->array->getValue());
        $this->assertEquals(array(0 => 'baz', 'foo' => 'bar'), $this->array->getValue());
    }

    public function testArrayMagicInvoke()
    {
        $arr = $this->array;
        $this->assertEquals($this->array->getValue(), $arr());
    }

    public function testArrayMagicGet()
    {
        //Test existing key
        $this->assertEquals('bar', $this->array->foo);
        //Test non existing key
        $this->assertEquals(null, $this->array->baz);
    }

    public function testArrayPrepend()
    {
        $this->array->prepend('baz', 'bat');
        $this->assertEquals(array('baz', 'bat', 'foo' => 'bar'), $this->array->getValue());
    }

    public function testIsValidWithInternalArrayDataExpectSingleError()
    {
        $val = new Zend_Validate_Identical();
        $val->setToken(array('foo' => 'bar'));
        $this->array->addValidator($val);

        $this->array->append('baz');
        $this->assertCount(0, $this->array->getMessages());
        $this->assertEquals(false, $this->array->isValid());
        $this->assertInstanceOf('Enrise_Array', $this->array->getMessages());
        $this->assertCount(1, $this->array->getMessages());
        $this->assertArrayHasKey('notSame', $this->array->getMessages()->current());
    }

    public function testIsValidWithExternalArrayDataExpectSingleError()
    {
        $val = new Zend_Validate_Identical();
        $val->setToken(array('foo' => 'bar'));
        $this->array->addValidator($val);

        $this->assertCount(0, $this->array->getMessages());
        $this->assertEquals(false, $this->array->isValid('bar'));
        $this->assertInstanceOf('Enrise_Array', $this->array->getMessages());
        $this->assertCount(1, $this->array->getMessages());

        $cur = $this->array->getMessages()->current();
        $this->assertArrayHasKey('notSame', $cur);
        $this->assertEquals(
            "The token 'Array' does not match the given token 'bar'",
            $cur['notSame']
        );
    }

    public function testIsValidWithInternalDataExpectNoError()
    {
        $val = new Zend_Validate_Identical();
        $val->setToken(array('foo' => 'bar'));
        $this->array->addValidator($val);

        $this->assertEquals(true, $this->array->isValid());
        $this->assertInstanceOf('Enrise_Array', $this->array->getMessages());
        $this->assertCount(0, $this->array->getMessages());
    }

    public function testIsValidWithExternalArrayDataExpectNoError()
    {
        $val = new Zend_Validate_Identical();
        $val->setToken(array('foo' => 'bar'));
        $this->array->addValidator($val);

        $this->assertEquals(true, $this->array->isValid(array('foo' => 'bar')));
        $this->assertInstanceOf('Enrise_Array', $this->array->getMessages());
        $this->assertCount(0, $this->array->getMessages());
    }

    public function testIsValidBreakChainOnFailure()
    {
        $val = new Zend_Validate_Identical();
        $val->setToken(array('foo' => 'bar'));
        $this->array->addValidator($val);
        $this->array->addValidator(new Zend_Validate_Alnum());
        $this->array->addValidator(new Zend_Validate_NotEmpty());

        $this->assertEquals(false, $this->array->isValid(array('foo' => 'bar', 'baz'), true));
        $this->assertInstanceOf('Enrise_Array', $this->array->getMessages());
        $cur = $this->array->getMessages()->current();
        $this->assertCount(1, $cur);
        //Only test for messages from identical validator as the chain should break
        $this->assertArrayHasKey('notSame', $cur);
    }

    public function testfilterByValidate()
    {
        $this->array->append('abcd');
        $this->array->addValidator(new Zend_Validate_StringLength(array('max' => 3)));
        $this->assertEquals(array('foo' => 'bar'), $this->array->filterByValidate()->getValue());
    }

    public function testArrayIntersect()
    {
        $this->array->append('abcd');
        $this->assertEquals(array('abcd'), $this->array->intersect(array('abcd'))->getValue());
    }

    public function testArrayIntersectKey()
    {
        $data = array('abcd' => 'efgh');
        $this->array->merge($data);
        $this->assertEquals($data, $this->array->intersectKey($data)->getValue());
    }
}

