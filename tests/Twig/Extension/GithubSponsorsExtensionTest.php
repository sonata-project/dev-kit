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

use App\Domain\Value\Repository;
use App\Twig\Extension\GithubSponsorsExtension;
use Github\Api\Repo;
use Github\Client as GithubClient;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GithubSponsorsExtensionTest extends TestCase
{
    /**
     * @var GithubClient&MockObject
     */
    private GithubClient $github;

    private GithubSponsorsExtension $githubSponsorsExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->github = $this->createMock(GithubClient::class);
        $this->githubSponsorsExtension = new GithubSponsorsExtension($this->github);
    }

    /**
     * @test
     */
    public function listSponsorisableIsFilteredAndOrdered(): void
    {
        $package = new Package();
        $package->fromArray(['repository' => 'https://github.com/sonata-project/SonataAdminBundle']);

        $repo = $this->createMock(Repo::class);
        $repo->expects(static::once())
            ->method('contributors')
            ->with('sonata-project', 'SonataAdminBundle')
            ->willReturn([
                ['login' => 'OskarStark'],
                ['login' => 'nobody'],
                ['login' => 'VincentLanglet'],
                ['login' => 'greg0ire'],
            ]);

        $this->github
            ->expects(static::once())
            ->method('__call')
            ->with('repo')
            ->willReturn($repo);

        static::assertSame(
            ['OskarStark', 'VincentLanglet', 'greg0ire'],
            $this->githubSponsorsExtension->listSponsorisable(Repository::fromPackage($package)),
        );
    }

    /**
     * @test
     */
    public function listSponsorisableOnlyReturnFourSponsors(): void
    {
        $package = new Package();
        $package->fromArray(['repository' => 'https://github.com/sonata-project/SonataAdminBundle']);

        $repo = $this->createMock(Repo::class);
        $repo->expects(static::once())
            ->method('contributors')
            ->with('sonata-project', 'SonataAdminBundle')
            ->willReturn([
                ['login' => 'OskarStark'],
                ['login' => 'SonataBot'],
                ['login' => 'VincentLanglet'],
                ['login' => 'core23'],
                ['login' => 'wbloszyk'],
                ['login' => 'greg0ire'],
            ]);

        $this->github
            ->expects(static::once())
            ->method('__call')
            ->with('repo')
            ->willReturn($repo);

        static::assertSame(
            ['OskarStark', 'VincentLanglet', 'core23', 'wbloszyk'],
            $this->githubSponsorsExtension->listSponsorisable(Repository::fromPackage($package)),
        );
    }
}
