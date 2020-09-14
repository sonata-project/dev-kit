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

namespace App\Tests\Github;

use App\Github\Domain\Value\Issue\IssueId;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\Repository;
use App\Github\GithubClient;
use Github\Api\Issue;
use Github\Client;
use PHPUnit\Framework\TestCase;

final class GithubClientTest extends TestCase
{
    /**
     * @test
     */
    public function addIssueLabelAddsLabelIfItDoesNotExist(): void
    {
        $repository = Repository::fromString('sonata-project/SonataAdminBundle');
        $issueId = IssueId::fromInt(42);
        $label = Label::fromString('foo');

        $labels = $this->createMock(Issue\Labels::class);
        $labels->method('all')
            ->willReturn([
                ['name' => 'bar'],
            ]);
        $labels->expects($this->once())
            ->method('add')
            ->with(
                $repository->username(),
                $repository->name(),
                $issueId->toInt(),
                $label->toString()
            );

        $issues = $this->createMock(Issue::class);
        $issues->method('labels')
            ->willReturn($labels);

        $client = $this->createMock(Client::class);
        $client
            ->method('issues')
            ->willReturn($issues);

        $SUT = new GithubClient($client);
        $SUT->addIssueLabel($repository, $issueId, $label);
    }
}
