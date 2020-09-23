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

use App\Github\Domain\Value\PullRequest;
use App\Tests\Util\Factory\PullRequestResponseFactory;
use App\Tests\Util\Helper;
use PHPUnit\Framework\TestCase;

final class PullRequestTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse([]);
    }

    /**
     * @test
     */
    public function usesTitleFromResponse()
    {
        $value = self::faker()->sentence;

        $response = PullRequestResponseFactory::create([
            'title' => $value,
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        self::assertSame($value, $pullRequest->title());
    }

    /**
     * @test
     *
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::blank()
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionIfTitleIs(string $value)
    {
        $response = PullRequestResponseFactory::create([
            'title' => $value,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $response = [
            'number' => $issue = 123,
            'title' => $title = 'Update dependecy',
            'updated_at' => $updatedAt = '2020-01-01 19:00:00',
            'base' => [
                'ref' => $baseRef = 'baseRef',
            ],
            'head' => [
                'ref' => $headRef = 'headRef',
                'sha' => $headSha = 'sha',
                'repo' => [
                    'owner' => [
                        'login' => $ownerLogin = 'ownerLogin',
                    ],
                ],
            ],
            'user' => [
                'login' => $userLogin = 'userLogin',
                'html_url' => $userHtmlUrl = 'https://test.com',
            ],
            'mergeable' => true,
            'body' => $body = 'The body!',
            'html_url' => $htmlUrl = 'https://test.com',
            'labels' => [
                [
                    'name' => $labelName = 'patch',
                    'color' => $labelColor = 'ededed',
                ],
            ],
        ];

        $pr = PullRequest::fromResponse($response);

        self::assertSame($issue, $pr->issue()->toInt());
        self::assertSame($title, $pr->title());
        self::assertSame($updatedAt, $pr->updatedAt()->format('Y-m-d H:i:s'));
        self::assertSame($baseRef, $pr->base()->ref());
        self::assertSame($headRef, $pr->head()->ref());
        self::assertSame($headSha, $pr->head()->sha()->toString());
        self::assertSame($ownerLogin, $pr->head()->repo()->owner()->login());
        self::assertSame($userLogin, $pr->user()->login());
        self::assertSame($userHtmlUrl, $pr->user()->htmlUrl());
        self::assertTrue($pr->isMergeable());
        self::assertSame($body, $pr->body());
        self::assertSame($htmlUrl, $pr->htmlUrl());
        self::assertTrue($pr->hasLabels());

        $label = $pr->labels()[0];
        self::assertSame($labelName, $label->name());
        self::assertSame($labelColor, $label->color()->toString());
    }

    /**
     * @test
     */
    public function updatedWithinTheLast60SecondsReturnsTrue(): void
    {
        $now = new \DateTimeImmutable(
            'now',
            new \DateTimeZone('UTC')
        );

        $response = [
            'number' => $issue = 123,
            'title' => $title = 'Update dependecy',
            'updated_at' => $now->format('Y-m-d H:i:s'),
            'base' => [
                'ref' => $baseRef = 'baseRef',
            ],
            'head' => [
                'ref' => $headRef = 'headRef',
                'sha' => $headSha = 'sha',
                'repo' => [
                    'owner' => [
                        'login' => $ownerLogin = 'ownerLogin',
                    ],
                ],
            ],
            'user' => [
                'login' => $userLogin = 'userLogin',
                'html_url' => $userHtmlUrl = 'https://test.com',
            ],
            'mergeable' => true,
            'body' => '',
            'html_url' => $htmlUrl = 'https://test.com',
            'labels' => [],
        ];

        $pr = PullRequest::fromResponse($response);

        self::assertTrue($pr->updatedWithinTheLast60Seconds());
    }

    /**
     * @test
     */
    public function updatedWithinTheLast60SecondsReturnsFalse(): void
    {
        $now = new \DateTimeImmutable(
            '2020-01-01 19:00:00',
            new \DateTimeZone('UTC')
        );

        $response = [
            'number' => $issue = 123,
            'title' => $title = 'Update dependecy',
            'updated_at' => $now->format('Y-m-d H:i:s'),
            'base' => [
                'ref' => $baseRef = 'baseRef',
            ],
            'head' => [
                'ref' => $headRef = 'headRef',
                'sha' => $headSha = 'sha',
                'repo' => [
                    'owner' => [
                        'login' => $ownerLogin = 'ownerLogin',
                    ],
                ],
            ],
            'user' => [
                'login' => $userLogin = 'userLogin',
                'html_url' => $userHtmlUrl = 'https://test.com',
            ],
            'mergeable' => true,
            'body' => '',
            'html_url' => $htmlUrl = 'https://test.com',
            'labels' => [],
        ];

        $pr = PullRequest::fromResponse($response);

        self::assertFalse($pr->updatedWithinTheLast60Seconds());
    }
}
