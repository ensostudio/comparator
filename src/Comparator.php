<?php

namespace EnsoStudio\Comparator;

use Closure;
use ReflectionFunction;

use function abs;
use function array_keys;
use function count;
use function ksort;
use function get_class;
use function get_object_vars;
use function get_resource_type;
use function gettype;
use function in_array;
use function is_nan;
use function max;
use function method_exists;
use function min;
use function strcasecmp;
use function strcmp;
use function stream_get_meta_data;

use const PHP_FLOAT_EPSILON;

/**
 * The flexible comparation.
 */
class Comparator
{
    /**
     * The compares values must have same type.
     */
    public const STRICT = 1;

    /**
     * The compares objects can be equal: instances of same class and their properties are equal (objects comparasion).
     */
    public const EQUAL_OBJECT = 2;

    /**
     * The compares closures can be equal: defined in same file&line and their scopes are same (objects comparasion).
     */
    public const EQUAL_CLOSURE = 4;

    /**
     * The compares float numbers can be equal: +/- epsilon (floats comparasion).
     */
    public const EQUAL_FLOAT = 8;

    /**
     * The items/properties are equal if they are `NAN` (floats comparasion).
     */
    public const EQUAL_NAN = 16;

    /**
     * The compares index arrays can be equal: values are equals, order no matter (arrays comparasion).
     */
    public const EQUAL_ARRAY = 32;

    /**
     * The compares index arrays can be equal: values are equals, order no matter (arrays comparasion).
     */
    public const EQUAL_INDEX_ARRAY = 64;

    /**
     * The binary safe case-insensitive string comparison (strings comparasion).
     */
    public const EQUAL_STRING = 128;

    /**
     * The streams are equal if their meta data are equal (resources comparasion).
     */
    public const EQUAL_STREAM = 256;

    /**
     * @var int The flags defines comparison behavior.
     */
    private $flags = 0;

    /**
     * @param int $flags The flags to control the comparasion behavior. It takes on either a bitmask, or self constants.
     * @return void
     */
    public function __construct(
        int $flags = self::EQUAL_CLOSURE | self::EQUAL_OBJECT | self::EQUAL_ARRAY | self::EQUAL_FLOAT
    ) {
        $this->setFlags($flags);
    }

    /**
     * Sets the behavior flags.
     *
     * @param int $flags The flags to control the comparasion behavior. It takes on either a bitmask, or self constants.
     * @return void
     */
    public function setFlags(int $flags)
    {
        $this->flags = max(0, $flags);
    }

    /**
     * Gets the behavior flags.
     *
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * Checks if the given behavior flag is set by default.
     *
     * @param int $flag The behavior flag to check.
     * @return bool
     */
    public function hasFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    /**
     * Compares two values.
     *
     * @param mixed $value the first value to compare
     * @param mixed $value2 the second value to compare
     * @return bool
     */
    public function compare($value, $value2): bool
    {
        if ($value === $value2 || (!$this->hasFlag(self::STRICT) && $value == $value2)) {
            return true;
        }

        $type = gettype($value);
        $type2 = gettype($value2);
        if ($this->hasFlag(self::STRICT) && $type !== $type2) {
            return false;
        }
        if ($type === 'double' || $type2 === 'double') {
            $type = 'float';
        } elseif ($type === 'string' || $type2 === 'string') {
            $type = 'string';
        }
        if (
            ($type === $type2 && in_array($type, ['array', 'object', 'resource'], true))
            || in_array($type, ['float', 'string'], true)
        ) {
            return $this->{'compare' . $type . 's'}($value, $value2);
        }

        return false;
    }

    /**
     * Compares two decimal numbers.
     *
     * @param float $number the first number to compare
     * @param float $number2 the second number to compare
     * @return bool
     */
    protected function compareFloats(float $number, float $number2): bool
    {
        $isNan = is_nan($number);
        $isNan2 = is_nan($number2);
        if ($isNan || $isNan2) {
            return $isNan && $isNan2 && $this->hasFlag(self::EQUAL_NAN);
        }

        if ($this->hasFlag(self::EQUAL_FLOAT)) {
            return abs($number - $number2) < PHP_FLOAT_EPSILON
                || (min($number, $number2) + PHP_FLOAT_EPSILON === max($number, $number2) - PHP_FLOAT_EPSILON);
        }

        return false;
    }

    /**
     * Compares two strings.
     *
     * @param string $string the first string to compare
     * @param string $string2 the second string to compare
     * @return bool
     */
    protected function compareStrings(string $string, string $string2): bool
    {
        $diff = $this->hasFlag(self::EQUAL_STRING) ? strcasecmp($string, $string2) : strcmp($string, $string2);

        return $diff === 0;
    }

    /**
     * Compares two arrays.
     *
     * @param array $array the first array to compare
     * @param array $array2 the second array to compare
     * @return bool
     */
    protected function compareArrays(array $array, array $array2): bool
    {
        if (count($array) !== count($array2)) {
            return false;
        }

        if ($this->hasFlag(self::EQUAL_ARRAY)) {
            ksort($array);
            ksort($array2);
        }
        $keys = array_keys($array);
        if ($keys != array_keys($array2)) {
            return false;
        }

        if ($this->hasFlag(self::EQUAL_INDEX_ARRAY) && $keys === array_keys($keys)) {
            // sort values in index arrays
            sort($array);
            sort($array2);
        }

        foreach ($array as $key => $value) {
            if (!$this->compare($value, $array2[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compares two resources.
     *
     * @param resource $resource the first resource to compare
     * @param resource $resource2 the second resource to compare
     * @return bool
     */
    protected function compareResources($resource, $resource2): bool
    {
        if ($this->hasFlag(self::EQUAL_STREAM)) {
            $type = get_resource_type($resource);
            if ($type === 'stream' && $type === get_resource_type($resource2)) {
                return $this->compareArrays(stream_get_meta_data($resource), stream_get_meta_data($resource2));
            }
        }

        return false;
    }

    /**
     * Compares two objects.
     *
     * @param object $object the first object to compare
     * @param object $object2 the second object to compare
     * @return bool
     */
    protected function compareObjects(object $object, object $object2): bool
    {
        if (get_class($object) !== get_class($object2)) {
            return false;
        }

        if ($object instanceof Closure) {
            if ($this->hasFlag(self::EQUAL_CLOSURE)) {
                $rf = new ReflectionFunction($object);
                $rf2 = new ReflectionFunction($object2);
                $scope = $rf->getClosureThis();
                $scope2 = $rf2->getClosureThis();

                return ($this->hasFlag(self::EQUAL_OBJECT) ? $scope == $scope2 : $scope === $scope2)
                    && (string) $rf === (string) $rf2;
            }

            return false;
        }

        if ($this->hasFlag(self::EQUAL_OBJECT)) {
            return method_exists($object, '__toString')
                ? $this->compareStrings((string) $object, (string) $object2)
                : $this->compareArrays(get_object_vars($object), get_object_vars($object2));
        }

        return false;
    }
}
