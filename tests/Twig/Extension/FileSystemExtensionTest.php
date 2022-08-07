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

namespace App\Tests\Twig\Extension;

use App\Twig\Extension\FileSystemExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class FileSystemExtensionTest extends TestCase
{
    /**
     * @var Filesystem&MockObject
     */
    private Filesystem $fileSystem;

    private FileSystemExtension $fileSystemExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystem = $this->createMock(Filesystem::class);
        $this->fileSystemExtension = new FileSystemExtension($this->fileSystem);
    }

    /**
     * @test
     */
    public function hasDependency(): void
    {
        static::assertTrue($this->fileSystemExtension->hasDependency(
            __DIR__.'/../../..',
            'phpunit/phpunit'
        ));

        static::assertFalse($this->fileSystemExtension->hasDependency(
            __DIR__.'/../../..',
            'sonata-project/admin-bundle'
        ));
    }
}
