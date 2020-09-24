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

namespace App\Tests\Action;

use App\Action\DetermineNextReleaseVersion;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\PullRequest;
use App\Tests\Util\Helper;
use PHPUnit\Framework\TestCase;
use App\Github\Domain\Value\Release\Tag;
use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

final class DetermineNextReleaseVersionTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function returnsCurrentIfNoPullRequestsAreProvider()
    {
        $tag = Tag::fromString('1.1.0');

        self::assertSame(
            $tag,
            (new DetermineNextReleaseVersion())->__invoke($tag, [])
        );
    }

    /**
     * @test
     */
    public function returnsCurrentIfNoMinorOrPatchStabilityIsFound()
    {
        $tag = Tag::fromString('1.1.0');

        $pullRequests = [
            self::createPullRequestWithStability('unknown'),
        ];

        self::assertSame(
            $tag,
            (new DetermineNextReleaseVersion())->__invoke($tag, $pullRequests)
        );
    }

    /**
     * @test
     *
     * @dataProvider determineProvider
     */
    public function determine(string $expected, string $current, array $pullRequets)
    {
        $tag = Tag::fromString($current);

        $nextVersion = (new DetermineNextReleaseVersion())->__invoke($tag, $pullRequets)->toString();

        self::assertSame(
            $expected,
            $nextVersion
        );
    }


    public function determineProvider(): \Generator
    {
        yield [
            '1.1.0',
            '1.2.0',
            [
                self::createPullRequestWithStability('unkown'),
                self::createPullRequestWithStability('minor'),
                self::createPullRequestWithStability('patch'),
            ]
        ];

        yield [
            '1.1.0',
            '1.1.1',
            [
                self::createPullRequestWithStability('unkown'),
                self::createPullRequestWithStability('patch'),
            ]
        ];
    }

    private static function createPullRequestWithStability(string $stability): PullRequest
    {
        Assert::oneOf(
            $stability,
            [
                'unknown',
                'minor',
                'patch',
            ]
        );

        $response = PullRequestResponseFactory::create();

        if ('unknown' === $stability) {
            $response['labels'] = [];
        } else {
            $response['labels'] = [
                'name' => $stability,
                'color' => u(self::faker()->hexColor)->replace('#', '')->toString()
            ];
        }

        $pullRequest = PullRequest::fromResponse($response);

        self::assertSame($stability, $pullRequest->stability());
    }
}
