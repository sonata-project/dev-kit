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

use App\Domain\Value\Stability;
use App\Github\Domain\Value\PullRequest;
use App\Tests\Util\Factory\Github;
use App\Tests\Util\Helper;
use PHPUnit\Framework\TestCase;

final class PullRequestTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function throwsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse([]);
    }

    /**
     * @test
     */
    public function usesNumberFromResponse()
    {
        $response = Github\Response\PullRequestFactory::create();

        $pullRequest = PullRequest::fromResponse($response);

        self::assertSame($response['number'], $pullRequest->issue()->toInt());
    }

    /**
     * @test
     */
    public function throwsExceptionIfNumberIsNotSet()
    {
        $response = Github\Response\PullRequestFactory::create();
        unset($response['number']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfNumberIsZero()
    {
        $response = Github\Response\PullRequestFactory::create([
            'number' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfNumberIsNgeative()
    {
        $response = Github\Response\PullRequestFactory::create([
            'number' => -1,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function usesTitleFromResponse()
    {
        $value = self::faker()->sentence;

        $response = Github\Response\PullRequestFactory::create([
            'title' => $value,
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        self::assertSame($value, $pullRequest->title());
    }

    /**
     * @test
     */
    public function throwsExceptionIfTitleIsNotSet()
    {
        $response = Github\Response\PullRequestFactory::create();
        unset($response['title']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::blank()
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionIfTitleIs(string $value)
    {
        $response = Github\Response\PullRequestFactory::create([
            'title' => $value,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function usesUpdatedAtFromResponse()
    {
        $response = Github\Response\PullRequestFactory::create([
            'updated_at' => $value = self::faker()->date('Y-m-d\TH:i:s\Z'),
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        self::assertSame(
            $value,
            $pullRequest->updatedAt()->format('Y-m-d\TH:i:s\Z')
        );
    }

    /**
     * @test
     */
    public function throwsExceptionIfUpdatedAtIsNotSet()
    {
        $response = Github\Response\PullRequestFactory::create();
        unset($response['updated_at']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::blank()
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionIfUpdatedAtIs(string $value)
    {
        $response = Github\Response\PullRequestFactory::create([
            'updated_at' => $value,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function usesMergedAtFromResponse()
    {
        $response = Github\Response\PullRequestFactory::create([
            'merged_at' => $value = self::faker()->date('Y-m-d\TH:i:s\Z'),
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        self::assertSame(
            $value,
            $pullRequest->mergedAt()->format('Y-m-d\TH:i:s\Z')
        );
        self::assertTrue($pullRequest->isMerged());
    }

    /**
     * @test
     */
    public function mergedAtCanBeNull()
    {
        $response = Github\Response\PullRequestFactory::create([
            'merged_at' => null,
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        self::assertNull($pullRequest->mergedAt());
        self::assertFalse($pullRequest->isMerged());
    }

    /**
     * @test
     */
    public function throwsExceptionIfMergedAtIsNotSet()
    {
        $response = Github\Response\PullRequestFactory::create();
        unset($response['merged_at']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::blank()
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionIfMergedAtIs(string $value)
    {
        $response = Github\Response\PullRequestFactory::create([
            'merged_at' => $value,
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
            'number' => 123,
            'title' => 'Update dependecy',
            'updated_at' => '2020-01-01T19:00:00Z',
            'merged_at' => '2020-01-01T19:00:00Z',
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

        $response = Github\Response\PullRequestFactory::create([
            'updated_at' => $now->format('Y-m-d\TH:i:s\Z'),
        ]);

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

        $response = Github\Response\PullRequestFactory::create([
            'updated_at' => $now->format('Y-m-d\TH:i:s\Z'),
        ]);

        $pr = PullRequest::fromResponse($response);

        self::assertFalse($pr->updatedWithinTheLast60Seconds());
    }

    /**
     * @test
     *
     * @dataProvider stabilityProvider
     */
    public function stability(Stability $expected, array $labels): void
    {
        $response = Github\Response\PullRequestFactory::create([
            'labels' => $labels,
        ]);

        $pr = PullRequest::fromResponse($response);

        self::assertSame(
            $expected->toString(),
            $pr->stability()->toString()
        );
    }

    /**
     * @return \Generator<array{0: Stability, 1: array<mixed>}>
     */
    public function stabilityProvider(): \Generator
    {
        yield [
            Stability::unknown(),
            [],
        ];

        yield [
            Stability::unknown(),
            [
                Github\Response\LabelFactory::create([
                    'name' => 'foo',
                ]),
            ],
        ];

        yield [
            Stability::patch(),
            [
                Github\Response\LabelFactory::create([
                    'name' => 'patch',
                ]),
            ],
        ];

        yield [
            Stability::minor(),
            [
                Github\Response\LabelFactory::create([
                    'name' => 'minor',
                ]),
            ],
        ];

        yield [
            Stability::pedantic(),
            [
                Github\Response\LabelFactory::create([
                    'name' => 'pedantic',
                ]),
            ],
        ];

        yield [
            Stability::pedantic(),
            [
                Github\Response\LabelFactory::create([
                    'name' => 'docs',
                ]),
            ],
        ];
    }

    /**
     * @test
     */
    public function body(): void
    {
        $response = Github\Response\PullRequestFactory::create([
            'body' => sprintf(
                <<<BODY
<!-- %s -->

## Subject

%s

## Changelog

```markdown
### Changed
- %s
```
BODY,
                self::faker()->text,
                self::faker()->text,
                $message = 'The fourth argument of the `SetObjectFieldValueAction::__construct` method is now mandatory.'
            ),
        ]);

        $pr = PullRequest::fromResponse($response);

        $changelog = $pr->changelog();

        self::assertArrayHasKey(
            'Changed',
            $changelog
        );
        self::assertStringContainsString(
            $message,
            $changelog['Changed'][0]
        );
    }
}
