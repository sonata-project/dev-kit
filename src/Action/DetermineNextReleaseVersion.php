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

namespace App\Action;

use App\Domain\Value\Stability;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Release\Tag;

final class DetermineNextReleaseVersion
{
    /**
     * @param PullRequest[] $pullRequests
     */
    public static function forTagAndPullRequests(Tag $current, array $pullRequests): Tag
    {
        if ([] === $pullRequests) {
            return $current;
        }

        $stabilities = array_map(static function (PullRequest $pr): string {
            return $pr->stability()->toString();
        }, $pullRequests);

        // Add compatibility for non-semantic versioning like `4.0.0-alpha-1`
        $currentTag = str_replace('-', '.', $current->toString());
        $parts = explode('.', $currentTag);

        if (isset($parts[3])) {
            return Tag::fromString(implode('.', [$parts[0], $parts[1], $parts[2]]));
        }

        if ('x' === $parts[1]) {
            return Tag::fromString(implode('.', [(int) $parts[0], 0, '0-alpha-1']));
        }

        if (\in_array(Stability::major()->toString(), $stabilities, true)) {
            return Tag::fromString(implode('.', [(int) $parts[0] + 1, 0, 0]));
        }

        if (\in_array(Stability::minor()->toString(), $stabilities, true)) {
            return Tag::fromString(implode('.', [$parts[0], (int) $parts[1] + 1, 0]));
        }

        if (\in_array(Stability::patch()->toString(), $stabilities, true)) {
            return Tag::fromString(implode('.', [$parts[0], $parts[1], (int) $parts[2] + 1]));
        }

        return $current;
    }
}
