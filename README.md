The flexible comparation of
- index/assoc arrays
- objects/closures
- floats/NANs
- binary/text strings
- stream resources

### Installation
Via [Composer](https://getcomposer.org):
~~~bash
composer require ensostudio/comparator
~~~
### Usage
~~~php
use EnsoStudio\Comparator\Comparator;
$comparator = Comparator(Comparator::EQUAL_ARRAY | Comparator::EQUAL_FLOAT);
if ($comparator->compare($value, $value2)) {
   echo 'same values';
}
~~~
~~~php
$comparator->setFlags(Comparator::EQUAL_FLOAT);
var_dump(3 - 2.4 == 0.6, $comparator->compare(3 - 2.4, 0.6));
// false, true

$comparator->setFlags(Comparator::EQUAL_STRING);
var_dump('foo' == 'FOO', $comparator->compare('foo', 'FOO'));
// false, true
// Case-issensetive comparation supports only for English:
var_dump($comparator->compare('я', 'Я'));
// false

$comparator->setFlags(Comparator::EQUAL_CLOSURE);
$createClosure = function () {
    return function ($value) {
        return $value * 2;
    };
};
var_dump($createClosure() == $createClosure(), $comparator->compare($createClosure(), $createClosure()));
// false, true

$comparator->setFlags(Comparator::EQUAL_ARRAY | Comparator::EQUAL_FLOAT);
var_dump($comparator->compare(
  ['float' => 2 - 1.6, 'int' => 3],
  ['int' => 3, 'float' => 0.4]
));
// true
~~~
### Public API
~~~php
namespace EnsoStudio\Comparator;
class Comparator
{
    public function __construct(int $flags);
    public function setFlags(int $flags);
    public function getFlags(): int;
    public function hasFlag(int $flag): bool;
    public function getType(mixed $value): string;
    public function canCompare(string $type, string $type2): bool;
    public function compare(mixed $value, mixed $value): bool;
}
~~~
