<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Github\Domain\Value\Webhook;

use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Event::fromString('');
    }

    /**
     * @test
     */
    public function fromJsonString(): void
    {
        $payload = [
            'action' => 'synchonize',

        ];

        $value = 'issue';

        self::assertSame(
            $value,
            Event::fromString($value)->toString()
        );
    }
}
