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
    public function returnsCurrentIfNoPullRequestsAreProvider(): void
    {
        $tag = Tag::fromString('1.1.0');

        static::assertSame(
            $tag,
            DetermineNextReleaseVersion::forTagAndPullRequests($tag, [])
        );
    }

    /**
     * @test
     */
    public function returnsCurrentIfNoMajorOrMinorOrPatchStabilityIsFound(): void
    {
        $tag = Tag::fromString('1.1.0');

        $pullRequests = [
            self::createPullRequestWithStability(Stability::unknown()),
        ];

        static::assertSame(
            $tag,
            DetermineNextReleaseVersion::forTagAndPullRequests($tag, $pullRequests)
        );
    }

    /**
     * @test
     *
     * @param array<PullRequest> $pullRequests
     *
     * @dataProvider determineProvider
     */
    public function determine(string $expected, string $current, array $pullRequests): void
    {
        $tag = Tag::fromString($current);

        $nextVersion = DetermineNextReleaseVersion::forTagAndPullRequests($tag, $pullRequests)->toString();

        static::assertSame(
            $expected,
            $nextVersion
        );
    }

    /**
     * @return iterable<array{string, string, array<PullRequest>}>
     */
    public function determineProvider(): iterable
    {
        yield [
            '2.0.0-alpha-1',
            '2.x',
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
            '2.0.0-alpha1',
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

        static::assertSame(
            $stability->toString(),
            $pullRequest->stability()->toString()
        );

        return $pullRequest;
    }
}
