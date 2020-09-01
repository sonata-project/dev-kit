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

use App\Config\ProjectsConfigurations;
use App\Domain\Value\Project;
use App\Github\Domain\Value\PullRequest;
use App\Github\GithubClient;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class MergeConflictsCommand extends AbstractCommand
{
    private ProjectsConfigurations $projectsConfigurations;
    private GithubClient $github;

    /**
     * @var array<string, Project>
     */
    private array $projects = [];

    private bool $apply;

    public function __construct(ProjectsConfigurations $projectsConfigurations, GithubClient $github)
    {
        parent::__construct();

        $this->projectConfigurations = $projectsConfigurations;
        $this->github = $github;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('merge-conflicts')
            ->setDescription('Comments non-mergeable pull requests, asking the author to solve conflicts.')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applies wanted requests')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->apply = $input->getOption('apply');
        if (!$this->apply) {
            $this->io->warning('This is a dry run execution. No change will be applied here.');
        }

        $this->projects = $this->projectsConfigurations->all();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->projects as $name => $project) {
            try {
                $this->io->title($project->name());

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

        foreach ($this->githubClient->pullRequests()->all(static::GITHUB_GROUP, $repository->name()) as $pull) {
            $pullRequest = PullRequest::fromResponse($pull);

            $response = $this->githubClient->pullRequests()->show(static::GITHUB_GROUP, $repository->name(), $pullRequest->number());
            $status = PullRequest\Status::fromResponse($response);

            // The value of the mergeable attribute can be true, false, or null.
            // If the value is null this means that the mergeability hasn't been computed yet.
            // @see: https://developer.github.com/v3/pulls/#get-a-single-pull-request
            if (!$status->isMergable()) {
                $comments = array_filter(
                    $this->githubPaginator->fetchAll($this->githubClient->issues()->comments(), 'all', [
                        static::GITHUB_GROUP,
                        $repository->name(),
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
                    $repository->name(),
                    $pullRequest->number(),
                ]);
                $lastCommit = end($commits);
                $lastCommitDate = new \DateTime($lastCommit['commit']['committer']['date']);

                if (!$lastCommentDate || $lastCommentDate < $lastCommitDate) {
                    if ($this->apply) {
                        $this->githubClient->issues()->comments()->create(static::GITHUB_GROUP, $repository->name(), $pullRequest->number(), [
                            'body' => 'Could you please rebase your PR and fix merge conflicts?',
                        ]);
                        $this->githubClient->addIssueLabel(
                            static::GITHUB_GROUP,
                            $repository->name(),
                            $pullRequest->number(),
                            'pending author'
                        );
                    }

                    $this->io->text(sprintf(
                        '#%d - %s',
                        $pullRequest->number(),
                        $pullRequest->title()
                    ));
                }
            }
        }
    }
}
