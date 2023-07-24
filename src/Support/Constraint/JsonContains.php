<?php

namespace Dachcom\Codeception\Support\Constraint;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\ArrayComparator;
use SebastianBergmann\Comparator\Factory;
use InvalidArgumentException;

class JsonContains extends Constraint
{
    protected array $jsonArray = [];
    protected mixed $expected;

    public function __construct(array $expected)
    {
        $this->expected = $expected;
    }

    /**
     * Evaluates the constraint for parameter $other. Returns true if the
     * constraint is met, false otherwise.
     */
    protected function matches(mixed $other) : bool
    {
         if (!is_string($other)) {
            throw new InvalidArgumentException('$jsonString param must be a string.');
        }

        $jsonDecode = json_decode($other, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($jsonDecode)) {
            $jsonDecode = [$jsonDecode];
        }

        $this->jsonArray = $jsonDecode;

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid json: %s. System message: %s.",
                    $other,
                    json_last_error_msg()
                ),
                json_last_error()
            );
        }

        if (!is_array($this->jsonArray)) {
            throw new AssertionFailedError('JSON response is not an array: ' . $other);
        }

        if ($this->containsArray($this->expected)) {
            return true;
        }

        $comparator = new ArrayComparator();
        $comparator->setFactory(new Factory);
        try {
            $comparator->assertEquals($this->expected, $this->jsonArray);
        } catch (ComparisonFailure $failure) {
            throw new ExpectationFailedException(
                "Response JSON does not contain the provided JSON\n",
                $failure
            );
        }

        return false;
    }

    public function containsArray(array $needle): bool
    {
        return (new ArrayContainsComparator($this->jsonArray))->containsArray($needle);
    }

    public function toString() : string
    {
        return '';
    }

    protected function failureDescription($other) : string
    {
        return '';
    }
}
