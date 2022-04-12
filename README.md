The flexible comparation of any values.
### Installation
Via Composer:
~~~bash
composer require ensostudio/comparator
~~~
### API
~~~php
namespace EnsoStudio\Comparator;
class Comparator
{
    public function __construct(int $flags);
    public function setFlags(int $flags);
    public function getFlags(): int;
    public function hasFlag(int $flag): bool;
    public function compare(mixed $value, mixed $value): bool;
}
~~~
### Usage
~~~php
use EnsoStudio\Comparator\Comparator;
$comparator = new Comparator(Comparator::EQUAL_ARRAY | Comparator::EQUAL_FLOAT);
if ($comparator->compare(['float' => 2 - 1.6, 'int' => 3], ['int' => 3, 'float' => 0.4])) {
    echo 'equal values';
}
~~~
