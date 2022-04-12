<?php

namespace EnsoStudio\Comparator;

/**
 * Mocked Comparator.
 */
class MockedComparator extends Comparator
{
    public function compareFloats(float $number, float $number2): bool
    {
        return parent::compareFloats($number, $number2);
    }

    public function compareArrays(array $array, array $array2): bool
    {
        return parent::compareArrays($array, $array2);
    }

    public function compareStrings(string $string, string $string2): bool
    {
        return parent::compareStrings($string, $string2);
    }

    public function compareResources($resource, $resource2): bool
    {
        return parent::compareResources($resource, $resource2);
    }

    public function compareObjects(object $object, object $object2): bool
    {
        return parent::compareObjects($object, $object2);
    }
}
