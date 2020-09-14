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

namespace App\Github;

use App\Github\Domain\Value\Issue\IssueId;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\Repository;
use Github\Client;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class GithubClient
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Adds a label from an issue if this one is not set.
     */
    public function addIssueLabel(Repository $repository, IssueId $issueId, Label $label): void
    {
        foreach ($this->client->issues()->labels()->all($repository->username(), $repository->name(), $issueId->toInt()) as $labelInfo) {
            if ($label->equals(Label::fromString($labelInfo['name']))) {
                return;
            }
        }

        $this->client->issues()->labels()->add(
            $repository->username(),
            $repository->name(),
            $issueId->toInt(),
            $label->toString()
        );
    }

    /**
     * Removes a label from an issue if this one is set.
     */
    public function removeIssueLabel(Repository $repository, IssueId $issueId, Label $label): void
    {
        foreach ($this->client->issues()->labels()->all($repository->username(), $repository->name(), $issueId->toInt()) as $labelInfo) {
            if ($label->equals(Label::fromString($labelInfo['name']))) {
                $this->client->issues()->labels()->remove(
                    $repository->username(),
                    $repository->name(),
                    $issueId->toInt(),
                    $label->toString()
                );

                break;
            }
        }
    }
}
