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

use App\Twig\Extension\GithubExtension;
use PHPUnit\Framework\TestCase;

final class GithubExtensionTest extends TestCase
{
    /**
     * @var GithubExtension
     */
    private $githubExtension;

    protected function setup(): void
    {
        $this->githubExtension = new GithubExtension();
    }

    /**
     * @dataProvider isDevBranchDataProvider
     */
    public function testIsDevBranch(bool $expected, string $branch): void
    {
        self::assertSame($expected, $this->githubExtension->isDevBranch($branch));
    }

    public function isDevBranchDataProvider(): iterable
    {
        yield [true, 'dev-master'];
        yield [true, 'dev-custom'];
        yield [false, 'development'];
        yield [false, '4.4'];
    }

    /**
     * @dataProvider isDevMasterDataProvider
     */
    public function testIsDevMaster(bool $expected, string $branch): void
    {
        self::assertSame($expected, $this->githubExtension->isDevMaster($branch));
    }

    public function isDevMasterDataProvider(): iterable
    {
        yield [true, 'dev-master'];
        yield [false, 'dev-custom'];
        yield [false, 'development'];
        yield [false, '4.4'];
    }
}
