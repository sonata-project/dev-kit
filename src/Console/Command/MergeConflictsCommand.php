<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit\Console\Command;

use Github\Exception\ExceptionInterface;
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class MergeConflictsCommand extends AbstractNeedApplyCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('merge-conflicts')
            ->setDescription('Comments non-mergeable pull requests, asking the author to solve conflicts.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->configs['projects'] as $name => $projectConfig) {
            try {
                $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);
                $this->io->title($package->getName());
                $this->checkPullRequests($package);
            } catch (ExceptionInterface $e) {
                $this->io->error('Failed with message: '.$e->getMessage());
            }
        }

        return 0;
    }

    private function checkPullRequests(Package $package)
    {
        $repositoryName = $this->getRepositoryName($package);

        foreach ($this->githubClient->pullRequests()->all(static::GITHUB_GROUP, $repositoryName) as $pullRequest) {
            $number = $pullRequest['number'];
            $pullRequest = $this->githubClient->pullRequests()->show(static::GITHUB_GROUP, $repositoryName, $number);

            // The value of the mergeable attribute can be true, false, or null.
            // If the value is null this means that the mergeability hasn't been computed yet.
            // @see: https://developer.github.com/v3/pulls/#get-a-single-pull-request
            if (false === $pullRequest['mergeable']) {
                $comments = array_filter(
                    $this->githubPaginator->fetchAll($this->githubClient->issues()->comments(), 'all', [
                        static::GITHUB_GROUP,
                        $repositoryName,
                        $number,
                    ]),
                    function ($comment) {
                        return $comment['user']['login'] === static::GITHUB_USER;
                    }
                );
                $lastComment = end($comments);
                $lastCommentDate = $lastComment ? new \DateTime($lastComment['created_at']) : null;

                $commits = $this->githubPaginator->fetchAll($this->githubClient->pullRequest(), 'commits', [
                    static::GITHUB_GROUP,
                    $repositoryName,
                    $number,
                ]);
                $lastCommit = end($commits);
                $lastCommitDate = new \DateTime($lastCommit['commit']['committer']['date']);

                if (!$lastCommentDate || $lastCommentDate < $lastCommitDate) {
                    if ($this->apply) {
                        $this->githubClient->issues()->comments()->create(static::GITHUB_GROUP, $repositoryName, $number, [
                            'body' => 'Could you please rebase your PR and fix merge conflicts?',
                        ]);
                        $this->githubClient->addIssueLabel(static::GITHUB_GROUP, $repositoryName, $number, 'pending author');
                    }

                    $this->io->text(sprintf('#%d - %s', $pullRequest['number'], $pullRequest['title']));
                }
            }
        }
    }
}
