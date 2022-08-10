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

use App\Github\Domain\Value\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    public function testThrowsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repository::fromString('');
    }

    public function testThrowsExceptionIfValueDoesNotContainSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repository::fromString('foo');
    }

    public function testThrowsExceptionIfValueContainSlashButAtEnd(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repository::fromString('foo/');
    }

    public function testThrowsExceptionIfValueContainSlashButAtStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repository::fromString('/foo');
    }

    public function testFromString(): void
    {
        $repository = Repository::fromString('sonata-project/SonataAdminBundle');

        static::assertSame('sonata-project', $repository->username());
        static::assertSame('SonataAdminBundle', $repository->name());
        static::assertSame('sonata-project/SonataAdminBundle', $repository->toString());
    }
}
