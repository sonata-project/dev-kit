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
use App\Domain\Value\Stability;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Release\Tag;
use App\Tests\Util\Factory\Github;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

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
            DetermineNextReleaseVersion::forTagAndPullRequests($tag, [])
        );
    }

    /**
     * @test
     */
    public function returnsCurrentIfNoMajorOrMinorOrPatchStabilityIsFound()
    {
        $tag = Tag::fromString('1.1.0');

        $pullRequests = [
            self::createPullRequestWithStability(Stability::unknown()),
        ];

        self::assertSame(
            $tag,
            DetermineNextReleaseVersion::forTagAndPullRequests($tag, $pullRequests)
        );
    }

    /**
     * @test
     *
     * @dataProvider determineProvider
     */
    public function determine(string $expected, string $current, array $pullRequests)
    {
        $tag = Tag::fromString($current);

        $nextVersion = DetermineNextReleaseVersion::forTagAndPullRequests($tag, $pullRequests)->toString();

        self::assertSame(
            $expected,
            $nextVersion
        );
    }

    /**
     * @return \Generator<array{0: string, 1: string, 2: array<PullRequest>}>
     */
    public function determineProvider(): \Generator
    {
        yield [
            '2.0.0',
            '1.1.0',
            [
                self::createPullRequestWithStability(Stability::unknown()),
                self::createPullRequestWithStability(Stability::major()),
                self::createPullRequestWithStability(Stability::minor()),
                self::createPullRequestWithStability(Stability::patch()),
            ],
        ];

        yield [
            '2.0.0',
            '2.0.0-alpha-1',
            [
                self::createPullRequestWithStability(Stability::unknown()),
                self::createPullRequestWithStability(Stability::major()),
                self::createPullRequestWithStability(Stability::minor()),
                self::createPullRequestWithStability(Stability::patch()),
            ],
        ];

        yield [
            '2.0.0',
            '2.0.0.alpha.1',
            [
                self::createPullRequestWithStability(Stability::unknown()),
                self::createPullRequestWithStability(Stability::major()),
                self::createPullRequestWithStability(Stability::minor()),
                self::createPullRequestWithStability(Stability::patch()),
            ],
        ];

        yield [
            '2.0.0',
            '1.1.0',
            [
                self::createPullRequestWithStability(Stability::unknown()),
                self::createPullRequestWithStability(Stability::major()),
                self::createPullRequestWithStability(Stability::minor()),
                self::createPullRequestWithStability(Stability::patch()),
            ],
        ];

        yield [
            '1.2.0',
            '1.1.0',
            [
                self::createPullRequestWithStability(Stability::unknown()),
                self::createPullRequestWithStability(Stability::minor()),
                self::createPullRequestWithStability(Stability::patch()),
            ],
        ];

        yield [
            '1.1.1',
            '1.1.0',
            [
                self::createPullRequestWithStability(Stability::unknown()),
                self::createPullRequestWithStability(Stability::patch()),
            ],
        ];
    }

    private static function createPullRequestWithStability(Stability $stability): PullRequest
    {
        $response = Github\Response\PullRequestFactory::create();

        if ($stability->equals(Stability::unknown())) {
            $response['labels'] = [];
        } else {
            $response['labels'] = [
                Github\Response\LabelFactory::create([
                    'name' => $stability->toString(),
                ]),
            ];
        }

        $pullRequest = PullRequest::fromResponse($response);

        self::assertSame(
            $stability->toString(),
            $pullRequest->stability()->toString()
        );

        return $pullRequest;
    }
}
