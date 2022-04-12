<?php

namespace EnsoStudio\Comparator;

use PHPUnit\Framework\TestCase;

/**
 * @covers Comparator
 */
class ComparatorTest extends TestCase
{
    public function testGetFlags()
    {
        $comparator = new Comparator(Comparator::STRICT);
        $this->assertSame(Comparator::STRICT, $comparator->getFlags());
        $comparator->setFlags(Comparator::EQUAL_CLOSURE | Comparator::EQUAL_FLOAT);
        $this->assertSame(Comparator::EQUAL_CLOSURE | Comparator::EQUAL_FLOAT, $comparator->getFlags());
    }

    public function testHasFlag()
    {
        $comparator = new Comparator(Comparator::EQUAL_CLOSURE | Comparator::EQUAL_OBJECT);
        $this->assertTrue($comparator->hasFlag(Comparator::EQUAL_CLOSURE));
        $this->assertFalse($comparator->hasFlag(Comparator::EQUAL_FLOAT));
    }

    public function testCompareResources()
    {
        $res = \fopen('https://www.php.net', 'r');
        $res2 = \fopen('https://www.php.net', 'r');


        $comparator = new MockedComparator(Comparator::STRICT);
        $this->assertFalse($comparator->compareResources(\tmpfile(), \tmpfile()));
        $this->assertFalse($comparator->compareResources($res, $res2));

        $comparator->setFlags(Comparator::EQUAL_STREAM);
        $this->assertTrue($comparator->compareResources($res, $res2));
        $this->assertFalse($comparator->compareResources($res, \fopen('https://www.php.net/support', 'r')));
    }

    public function testCompareFloats()
    {
        $nan = \acos(1.01);
        $comparator = new MockedComparator(Comparator::STRICT);
        $this->assertFalse($comparator->compareFloats(0.6, 3 - 2.4));
        $this->assertFalse($comparator->compareFloats($nan, $nan));

        $comparator->setFlags(Comparator::EQUAL_FLOAT);
        $this->assertTrue($comparator->compareFloats(0.6, 3 - 2.4));
        $comparator->setFlags(Comparator::EQUAL_NAN);
        $this->assertTrue($comparator->compareFloats($nan, $nan));
    }

    public function testCompareStrings()
    {
        $comparator = new MockedComparator(Comparator::STRICT);
        $this->assertFalse($comparator->compareStrings('foo', 'bar'));
        $this->assertFalse($comparator->compareStrings('foo', 'Foo'));
        $this->assertTrue($comparator->compareStrings('мой тест', 'мой тест'));

        $comparator->setFlags(Comparator::EQUAL_STRING);
        $this->assertFalse($comparator->compareStrings('foo', 'bar'));
        $this->assertTrue($comparator->compareStrings('foo', 'FoO'));
        $this->assertFalse($comparator->compareStrings('мой тест', 'мой ТЕСТ'));
    }

    public function testCompareArrays()
    {
        $comparator = new MockedComparator(Comparator::STRICT);
        $this->assertFalse($comparator->compareArrays(['foo', 'bar'], ['bar', 'foo']));
        $this->assertFalse($comparator->compareArrays(['b' => [1, 2], 'a' => 0], ['a' => 0, 'b' => [1, 2]]));

        $comparator->setFlags(Comparator::EQUAL_ARRAY);
        $this->assertFalse($comparator->compareArrays(['foo', 'bar'], ['bar', 'foo']));
        $this->assertTrue($comparator->compareArrays(['b' => [1, 2], 'a' => 0], ['a' => 0, 'b' => [1, 2]]));

        $comparator->setFlags(Comparator::EQUAL_INDEX_ARRAY);
        $this->assertTrue($comparator->compareArrays(['foo', 'bar'], ['bar', 'foo']));
    }

    public function testCompareObjects()
    {
        $stdObject = (object) ['foo' => 'bar', 'baZz' => true];
        $stdObject2 = (object) ['foo' => 'bar', 'baZz' => true];
        $dateTime = new \DateTime('21-05-2021 11:30');
        $dateTime2 = new \DateTime('21-05-2021 11:30');
        $createClosure = function () {
          return function () {
            return 1;
          };
        };
        $closure = $createClosure();
        $closure2 = $createClosure();


        $comparator = new MockedComparator(Comparator::EQUAL_OBJECT);
        $this->assertTrue($comparator->compareObjects($stdObject, $stdObject));
        $this->assertTrue($comparator->compareObjects($stdObject, $stdObject2));
        $this->assertTrue($comparator->compareObjects($dateTime, $dateTime2));

        $comparator->setFlags(Comparator::EQUAL_CLOSURE);
        $this->assertTrue($comparator->compareObjects($closure, clone $closure));
        $this->assertTrue($comparator->compareObjects($closure, $closure2));
    }

    /**
     * @depends testCompareFloats
     * @depends testCompareStrings
     * @depends testCompareResources
     * @depends testCompareArrays
     * @depends testCompareObjects
     */
    public function testCompare()
    {
        $createClosure = function () {
            return function () {
                return 1;
            };
        };

        $comparator = new Comparator();
        $this->assertTrue($comparator->compare(
            [
                'closure' => $createClosure(),
                'arrayObject' => new \ArrayObject(['foo' => 1, 'bar' => 2]),
                'float' => 0.4,
                'str' => 'тест'
            ],
            [
                'closure' => $createClosure(),
                'arrayObject' => new \ArrayObject(['bar' => 2,'foo' => 1]),
                'str' => 'тест',
                'float' => 3 - 2.6
            ]
        ));
    }
}
