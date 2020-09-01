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

namespace App\Command;

use App\Domain\Value\Project;
use App\Github\Domain\Value\PullRequest;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class MergeConflictsCommand extends AbstractNeedApplyCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('merge-conflicts')
            ->setDescription('Comments non-mergeable pull requests, asking the author to solve conflicts.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->configs['projects'] as $name => $config) {
            try {
                $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);

                $this->io->title($package->getName());
                $project = Project::fromValues($name, $config, $package);

                $this->checkPullRequests($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function checkPullRequests(Project $project): void
    {
        $repository = $project->repository();

        foreach ($this->githubClient->pullRequests()->all(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix()) as $pull) {
            dump($pull);
            $pullRequest = PullRequest::fromConfigArray($pull);

            $pullRequest = $this->githubClient->pullRequests()->show(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $pullRequest->number());

            dd($pullRequest);
            // The value of the mergeable attribute can be true, false, or null.
            // If the value is null this means that the mergeability hasn't been computed yet.
            // @see: https://developer.github.com/v3/pulls/#get-a-single-pull-request
            if (false === $pullRequest['mergeable']) {
                $comments = array_filter(
                    $this->githubPaginator->fetchAll($this->githubClient->issues()->comments(), 'all', [
                        static::GITHUB_GROUP,
                        $repository->nameWithoutVendorPrefix(),
                        $pullRequest->number(),
                    ]),
                    static function ($comment) {
                        return $comment['user']['login'] === static::GITHUB_USER;
                    }
                );
                $lastComment = end($comments);
                $lastCommentDate = $lastComment ? new \DateTime($lastComment['created_at']) : null;

                $commits = $this->githubPaginator->fetchAll($this->githubClient->pullRequest(), 'commits', [
                    static::GITHUB_GROUP,
                    $repository->nameWithoutVendorPrefix(),
                    $number,
                ]);
                $lastCommit = end($commits);
                $lastCommitDate = new \DateTime($lastCommit['commit']['committer']['date']);

                if (!$lastCommentDate || $lastCommentDate < $lastCommitDate) {
                    if ($this->apply) {
                        $this->githubClient->issues()->comments()->create(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $number, [
                            'body' => 'Could you please rebase your PR and fix merge conflicts?',
                        ]);
                        $this->githubClient->addIssueLabel(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $number, 'pending author');
                    }

                    $this->io->text(sprintf('#%d - %s', $pullRequest['number'], $pullRequest['title']));
                }
            }
        }
    }
}
