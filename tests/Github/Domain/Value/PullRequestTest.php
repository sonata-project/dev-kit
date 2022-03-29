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
use App\Tests\Util\Factory\Github\Response\LabelFactory;
use App\Tests\Util\Factory\Github\Response\PullRequestFactory;
use Ergebnis\Test\Util\Helper;
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
    public function usesNumberFromResponse(): void
    {
        $response = PullRequestFactory::create();

        $pullRequest = PullRequest::fromResponse($response);

        static::assertSame($response['number'], $pullRequest->issue()->toInt());
    }

    /**
     * @test
     */
    public function throwsExceptionIfNumberIsNotSet(): void
    {
        $response = PullRequestFactory::create();
        unset($response['number']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfNumberIsZero(): void
    {
        $response = PullRequestFactory::create([
            'number' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfNumberIsNgeative(): void
    {
        $response = PullRequestFactory::create([
            'number' => -1,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function usesTitleFromResponse(): void
    {
        $value = self::faker()->sentence();

        $response = PullRequestFactory::create([
            'title' => $value,
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        static::assertSame($value, $pullRequest->title());
    }

    /**
     * @test
     */
    public function throwsExceptionIfTitleIsNotSet(): void
    {
        $response = PullRequestFactory::create();
        unset($response['title']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionIfTitleIs(string $value): void
    {
        $response = PullRequestFactory::create([
            'title' => $value,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function usesUpdatedAtFromResponse(): void
    {
        $response = PullRequestFactory::create([
            'updated_at' => $value = self::faker()->date('Y-m-d\TH:i:s\Z'),
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        static::assertSame(
            $value,
            $pullRequest->updatedAt()->format('Y-m-d\TH:i:s\Z')
        );
    }

    /**
     * @test
     */
    public function throwsExceptionIfUpdatedAtIsNotSet(): void
    {
        $response = PullRequestFactory::create();
        unset($response['updated_at']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionIfUpdatedAtIs(string $value): void
    {
        $response = PullRequestFactory::create([
            'updated_at' => $value,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     */
    public function usesMergedAtFromResponse(): void
    {
        $response = PullRequestFactory::create([
            'merged_at' => $value = self::faker()->date('Y-m-d\TH:i:s\Z'),
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        static::assertNotNull($pullRequest->mergedAt());
        static::assertSame(
            $value,
            $pullRequest->mergedAt()->format('Y-m-d\TH:i:s\Z')
        );
        static::assertTrue($pullRequest->isMerged());
    }

    /**
     * @test
     */
    public function mergedAtCanBeNull(): void
    {
        $response = PullRequestFactory::create([
            'merged_at' => null,
        ]);

        $pullRequest = PullRequest::fromResponse($response);

        static::assertNull($pullRequest->mergedAt());
        static::assertFalse($pullRequest->isMerged());
    }

    /**
     * @test
     */
    public function throwsExceptionIfMergedAtIsNotSet(): void
    {
        $response = PullRequestFactory::create();
        unset($response['merged_at']);

        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionIfMergedAtIs(string $value): void
    {
        $response = PullRequestFactory::create([
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
            'title' => 'Update dependency',
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
                        'id' => $ownerId = 42,
                        'login' => $ownerLogin = 'ownerLogin',
                        'html_url' => $ownerHtmlUrl = 'http://example.com',
                    ],
                ],
            ],
            'user' => [
                'id' => $userId = 42,
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

        static::assertSame($baseRef, $pr->base()->ref());
        static::assertSame($headRef, $pr->head()->ref());
        static::assertSame($headSha, $pr->head()->sha()->toString());
        static::assertNotNull($pr->head()->repo());
        static::assertSame($ownerId, $pr->head()->repo()->owner()->id());
        static::assertSame($ownerLogin, $pr->head()->repo()->owner()->login());
        static::assertSame($ownerHtmlUrl, $pr->head()->repo()->owner()->htmlUrl());
        static::assertSame($userId, $pr->user()->id());
        static::assertSame($userLogin, $pr->user()->login());
        static::assertSame($userHtmlUrl, $pr->user()->htmlUrl());
        static::assertTrue($pr->isMergeable());
        static::assertSame($body, $pr->body());
        static::assertSame($htmlUrl, $pr->htmlUrl());
        static::assertTrue($pr->hasLabels());

        $label = $pr->labels()[0];
        static::assertSame($labelName, $label->name());
        static::assertSame($labelColor, $label->color()->toString());
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

        $response = PullRequestFactory::create([
            'updated_at' => $now->format('Y-m-d\TH:i:s\Z'),
        ]);

        $pr = PullRequest::fromResponse($response);

        static::assertTrue($pr->updatedWithinTheLast60Seconds());
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

        $response = PullRequestFactory::create([
            'updated_at' => $now->format('Y-m-d\TH:i:s\Z'),
        ]);

        $pr = PullRequest::fromResponse($response);

        static::assertFalse($pr->updatedWithinTheLast60Seconds());
    }

    /**
     * @test
     *
     * @param array<mixed> $labels
     *
     * @dataProvider stabilityProvider
     */
    public function stability(Stability $expected, array $labels): void
    {
        $response = PullRequestFactory::create([
            'labels' => $labels,
        ]);

        $pr = PullRequest::fromResponse($response);

        static::assertSame(
            $expected->toString(),
            $pr->stability()->toString()
        );
    }

    /**
     * @return iterable<array{Stability, array<mixed>}>
     */
    public function stabilityProvider(): iterable
    {
        yield [
            Stability::unknown(),
            [],
        ];

        yield [
            Stability::unknown(),
            [
                LabelFactory::create([
                    'name' => 'foo',
                ]),
            ],
        ];

        yield [
            Stability::patch(),
            [
                LabelFactory::create([
                    'name' => 'patch',
                ]),
            ],
        ];

        yield [
            Stability::minor(),
            [
                LabelFactory::create([
                    'name' => 'minor',
                ]),
            ],
        ];

        yield [
            Stability::pedantic(),
            [
                LabelFactory::create([
                    'name' => 'pedantic',
                ]),
            ],
        ];

        yield [
            Stability::pedantic(),
            [
                LabelFactory::create([
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
        $response = PullRequestFactory::create([
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
                self::faker()->text(),
                self::faker()->text(),
                $message = 'The fourth argument of the `SetObjectFieldValueAction::__construct` method is now mandatory.'
            ),
        ]);

        $pr = PullRequest::fromResponse($response);

        $changelog = $pr->changelog();

        static::assertArrayHasKey(
            'Changed',
            $changelog
        );
        static::assertStringContainsString(
            $message,
            $changelog['Changed'][0]
        );
    }
}
