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

use App\Config\Projects;
use App\Domain\Value\Project;
use App\Github\Api\Comments;
use App\Github\Api\Commits;
use App\Github\Api\Issues;
use App\Github\Api\PullRequests;
use App\Github\Domain\Value\Comment;
use App\Github\Domain\Value\Label;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class CommentNonMergeablePullRequestsCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private PullRequests $pullRequests;
    private Comments $comments;
    private Commits $commits;
    private Issues $issues;

    public function __construct(
        Projects $projects,
        PullRequests $pullRequests,
        Comments $comments,
        Commits $commits,
        Issues $issues
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->pullRequests = $pullRequests;
        $this->comments = $comments;
        $this->commits = $commits;
        $this->issues = $issues;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('comment-non-mergeable-pull-requests')
            ->setDescription('Comments non-mergeable pull requests, asking the author to solve conflicts.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Comment non-mergeable pull requests');

        foreach ($this->projects->all() as $project) {
            try {
                $this->io->section($project->name());

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

        $pullRequests = $this->pullRequests->all($repository);

        if ([] === $pullRequests) {
            $this->io->text('No pull requests available!');
        }

        foreach ($pullRequests as $pr) {
            $this->io->text(sprintf(
                '%s %s (%s)',
                $pr->issue()->toString(),
                $pr->title(),
                false === $pr->isMergeable() ? '<comment>not mergeable</comment>' : '<info>mergeable</info>',
            ));

            if (false === $pr->isMergeable()) {
                $lastComment = $this->comments->lastComment(
                    $repository,
                    $pr,
                    static::GITHUB_USER
                );

                $lastCommit = $this->commits->lastCommit(
                    $repository,
                    $pr
                );

                if (!$lastComment instanceof Comment
                    || $lastComment->before($lastCommit->date())
                ) {
                    $message = 'Could you please rebase your PR and fix merge conflicts?';

                    if (self::DEPENDABOT_BOT === $pr->user()->login()) {
                        $message = '@dependabot rebase';
                    }

                    $label = Label::PendingAuthor();

                    if ($this->apply) {
                        $this->comments->create(
                            $repository,
                            $pr->issue(),
                            $message
                        );

                        $this->issues->addLabel(
                            $repository,
                            $pr->issue(),
                            $label
                        );
                    }

                    $this->io->text(sprintf(
                        '    Comment: <info>%s</info>',
                        $message
                    ));
                    $this->io->text(sprintf(
                        '    Label:   <info>%s</info>',
                        $label->name()
                    ));
                    $this->io->newLine();
                }
            }
        }
    }
}
