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

namespace App\Tests\Github\Domain\Value;

use App\Github\Domain\Value\Event;
use App\Github\Domain\Value\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repository::fromString('');
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueDoesNotContainSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repository::fromString('foo');
    }

    /**
     * @test
     */
    public function fromString(): void
    {
        $repository = Repository::fromString('sonata-project/SonataAdminBundle');

        self::assertSame('sonata-project', $repository->vendor());
        self::assertSame('SonataAdminBundle', $repository->package());
        self::assertSame('sonata-project/SonataAdminBundle', $repository->toString());
    }
}
