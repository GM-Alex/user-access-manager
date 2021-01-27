<?php
declare(strict_types=1);

namespace UserAccessManager\Tests;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Exporter\Exporter;

/**
 * Class StringMatchIgnoreWhitespace
 * @package UserAccessManager\Tests
 */
class StringMatchIgnoreWhitespace extends Constraint
{
    private $expected;
    protected $exporter;

    public function __construct($expected)
    {
        $this->expected = $expected;
        $this->exporter = new Exporter();
    }

    protected function matches($other): bool
    {
        return $this->normalize($this->expected) == $this->normalize($other);
    }

    private function normalize(string $string)
    {
        return preg_replace('#\&. #','', implode(' ', preg_split('/\s+/', trim($string))));
    }

    public function toString(): string
    {
        return sprintf(
            'equals ignoring whitespace %s',
            $this->exporter->export($this->normalize($this->expected))
        );
    }
}