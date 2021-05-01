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

namespace App\Github\Domain\Value;

use App\Command\AbstractCommand;
use App\Domain\Value\Stability;
use App\Domain\Value\TrimmedNonEmptyString;
use App\Github\Domain\Value\PullRequest\Base;
use App\Github\Domain\Value\PullRequest\Head;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class PullRequest
{
    private Issue $issue;
    private string $title;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $mergedAt;
    private Base $base;
    private Head $head;
    private User $user;
    private ?bool $mergeable;
    private string $body;
    private string $htmlUrl;

    /**
     * @var Label[]
     */
    private array $labels;

    /**
     * @param Label[] $labels
     */
    private function __construct(
        Issue $issue,
        string $title,
        string $updatedAt,
        ?string $mergedAt,
        Base $base,
        Head $head,
        User $user,
        ?bool $mergeable,
        string $body,
        string $htmlUrl,
        array $labels
    ) {
        $this->issue = $issue;
        $this->title = TrimmedNonEmptyString::fromString($title)->toString();
        $this->updatedAt = new \DateTimeImmutable(
            TrimmedNonEmptyString::fromString($updatedAt)->toString(),
            new \DateTimeZone('UTC')
        );

        $this->mergedAt = null;
        if (null !== $mergedAt) {
            $this->mergedAt = new \DateTimeImmutable(
                TrimmedNonEmptyString::fromString($mergedAt)->toString(),
                new \DateTimeZone('UTC')
            );
        }

        $this->base = $base;
        $this->head = $head;
        $this->user = $user;
        $this->mergeable = $mergeable;
        $this->body = $body;
        $this->htmlUrl = TrimmedNonEmptyString::fromString($htmlUrl)->toString();
        $this->labels = $labels;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'number');

        Assert::keyExists($response, 'title');
        Assert::stringNotEmpty($response['title']);

        Assert::keyExists($response, 'updated_at');
        Assert::stringNotEmpty($response['updated_at']);

        Assert::keyExists($response, 'merged_at');
        Assert::nullOrStringNotEmpty($response['merged_at']);

        Assert::keyExists($response, 'base');
        Assert::notEmpty($response['base']);

        Assert::keyExists($response, 'head');
        Assert::notEmpty($response['head']);

        Assert::keyExists($response, 'user');
        Assert::notEmpty($response['user']);

        Assert::keyExists($response, 'mergeable');
        Assert::nullOrBoolean($response['mergeable']);

        Assert::keyExists($response, 'body');

        Assert::keyExists($response, 'html_url');
        Assert::stringNotEmpty($response['html_url']);

        Assert::keyExists($response, 'labels');
        $labels = [];
        foreach ($response['labels'] as $label) {
            $labels[] = Label::fromResponse($label);
        }

        return new self(
            Issue::fromInt($response['number']),
            $response['title'],
            $response['updated_at'],
            $response['merged_at'],
            Base::fromResponse($response['base']),
            Head::fromResponse($response['head']),
            User::fromResponse($response['user']),
            $response['mergeable'],
            $response['body'],
            $response['html_url'],
            $labels
        );
    }

    public function issue(): Issue
    {
        return $this->issue;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function mergedAt(): ?\DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function isMerged(): bool
    {
        return $this->mergedAt instanceof \DateTimeImmutable;
    }

    public function base(): Base
    {
        return $this->base;
    }

    public function head(): Head
    {
        return $this->head;
    }

    public function user(): User
    {
        return $this->user;
    }

    /**
     * The value of the mergeable attribute can be true, false, or null.
     * If the value is null this means that the mergeability hasn't been computed yet.
     *
     * @see: https://developer.github.com/v3/pulls/#get-a-single-pull-request
     */
    public function isMergeable(): ?bool
    {
        return $this->mergeable;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function htmlUrl(): string
    {
        return $this->htmlUrl;
    }

    /**
     * @return Label[]
     */
    public function labels(): array
    {
        return $this->labels;
    }

    public function hasLabels(): bool
    {
        return [] !== $this->labels;
    }

    public function updatedWithinTheLast60Seconds(): bool
    {
        $diff = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp()
            - $this->updatedAt->getTimestamp();

        return $diff < 60;
    }

    public function stability(): Stability
    {
        if ([] === $this->labels) {
            return Stability::unknown();
        }

        $labels = array_map(static function (Label $label): string {
            return $label->name();
        }, $this->labels);

        if (\in_array('major', $labels, true)) {
            return Stability::major();
        }

        if (\in_array('minor', $labels, true)) {
            return Stability::minor();
        }

        if (\in_array('patch', $labels, true)) {
            return Stability::patch();
        }

        if (array_intersect(['docs', 'pedantic'], $labels)) {
            return Stability::pedantic();
        }

        return Stability::unknown();
    }

    public function needsChangelog(): bool
    {
        return $this->stability()->notEquals(Stability::pedantic());
    }

    public function hasChangelog(): bool
    {
        return [] !== $this->changelog();
    }

    public function fulfilledChangelog(): bool
    {
        return !$this->needsChangelog() || ($this->needsChangelog() && $this->hasChangelog());
    }

    public function hasNotNeededChangelog(): bool
    {
        return !$this->needsChangelog() && $this->hasChangelog();
    }

    public function changelog(): array
    {
        $changelog = [];
        $body = preg_replace('/<!--(.*)-->/Uis', '', $this->body);
        preg_match('/## Changelog.*```\s*markdown\s*\\n(.*)\\n```/Uis', $body, $matches);

        if (2 === \count($matches)) {
            $lines = explode(\PHP_EOL, $matches[1]);

            $section = '';
            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                if (0 === strpos($line, '#')) {
                    $section = preg_replace('/^#* /i', '', $line);
                } elseif (!empty($section)) {
                    $line = preg_replace('/^- /i', '', $line);
                    $changelog[$section][] = sprintf(
                        '- [[#%s](%s)] %s ([@%s](%s))',
                        $this->issue->toInt(),
                        $this->htmlUrl,
                        ucfirst($line),
                        $this->user->login(),
                        $this->user->htmlUrl()
                    );
                }
            }
        }

        return $changelog;
    }

    public function createdAutomatically(): bool
    {
        if ('Applied fixes from FlintCI' === $this->title
            && 'soullivaneuh' === $this->user->login()
        ) {
            return true;
        }

        if (u($this->title)->startsWith('DevKit updates for')
            && AbstractCommand::SONATA_CI_BOT === $this->user->login()
        ) {
            return true;
        }

        return false;
    }
}
