<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Auth\RandomCodeGenerator;
use PHPUnit\Framework\TestCase;

final class RandomCodeGeneratorTest extends TestCase
{
    public function test_code_has_requested_length_and_only_digits(): void
    {
        $generator = new RandomCodeGenerator(6);

        foreach (range(1, 50) as $ignored) {
            $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $generator->generate());
        }
    }
}
