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

namespace App\Command\Dispatcher;

use App\Command\AbstractNeedApplyCommand;
use App\Config\Projects;
use App\Domain\Value\Project;
use App\Github\Api\Hooks;
use App\Github\Domain\Value\Hook;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchHooksCommand extends AbstractNeedApplyCommand
{
    /** @var string[] */
    private const HOOK_URLS_TO_BE_DELETED = [
        'https://api.codacy.com',
        'https://www.flowdock.com',
        'http://scrutinizer-ci.com',
        'http://localhost:8000',
        'https://notify.travis-ci.org',
    ];

    private Projects $projects;
    private Hooks $hooks;
    private string $devKitToken;

    public function __construct(
        Projects $projects,
        Hooks $hooks,
        string $devKitToken
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->hooks = $hooks;

        Assert::stringNotEmpty($devKitToken, '$devKitToken must not be an empty string!');
        $this->devKitToken = $devKitToken;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:hooks')
            ->setDescription('Dispatches hooks for all sonata projects.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch hooks for all sonata projects');

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->section($project->name());

                $this->updateDevKitHook($project);
                $this->deleteHooks($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateDevKitHook(Project $project): void
    {
        $repository = $project->repository();

        $this->io->writeln('    Check DevKit Hook for existence and configuration...');

        $devKitHookBaseUrl = 'https://master-7rqtwti-ptm4dx6rjpjko.eu-5.platformsh.site/github';

        $config = [
            'url' => sprintf(
                '%s?%s',
                $devKitHookBaseUrl,
                http_build_query([
                    'token' => $this->devKitToken,
                ])
            ),
            'insecure_ssl' => '0',
            'content_type' => 'json',
        ];

        $events = [
            'issue_comment',
            'pull_request',
            'pull_request_review_comment',
        ];

        // First, check if the DevKit Hook exists.
        $devKitHook = null;
        foreach ($this->hooks->all($repository) as $hook) {
            if (u($hook->config()->url()->toString())->startsWith($devKitHookBaseUrl)) {
                $devKitHook = $hook;

                break;
            }
        }

        if (!$devKitHook instanceof Hook) {
            $this->io->writeln('        Has to be created.');

            if ($this->apply) {
                $this->hooks->create(
                    $repository,
                    [
                        'name' => 'web',
                        'config' => $config,
                        'events' => $events,
                        'active' => true,
                    ]
                );

                $this->io->writeln('        <info>Hook created.</info>');
            }
        } elseif (
            !$devKitHook->config()->equals($config)
            || !$devKitHook->events()->equals($events)
            || !$devKitHook->active()
        ) {
            $this->io->writeln('        Has to be updated.');

            if ($this->apply) {
                $this->hooks->update(
                    $repository,
                    $devKitHook,
                    [
                        'name' => 'web',
                        'config' => $config,
                        'events' => $events,
                        'active' => true,
                    ]
                );

                $this->hooks->ping($repository, $devKitHook);

                $this->io->writeln('        <info>Hook updated.</info>');
            }
        } else {
            $this->io->writeln(sprintf(
                '        <comment>%s</comment>',
                static::LABEL_NOTHING_CHANGED
            ));
        }
        $this->io->newLine();
    }

    private function deleteHooks(Project $project): void
    {
        $repository = $project->repository();

        $this->io->writeln('    Check if some Hooks needs to be deleted...');

        $deleted = null;

        // Check if a Hook should be deleted.
        foreach ($this->hooks->all($repository) as $hook) {
            foreach (self::HOOK_URLS_TO_BE_DELETED as $url) {
                if (u($hook->url()->toString())->startsWith($url)) {
                    $deleted = true;
                    $this->io->writeln(sprintf(
                        '        Hook "%s" will be deleted',
                        $hook->url()->toString()
                    ));

                    if ($this->apply) {
                        $this->hooks->remove($repository, $hook);

                        $this->io->writeln(sprintf(
                            '        <info>Hook "%s" with ID %s deleted.</info>',
                            $hook->url()->toString(),
                            $hook->id()
                        ));
                    }
                }
            }
        }

        if (!$deleted) {
            $this->io->writeln(sprintf(
                '        <comment>%s</comment>',
                static::LABEL_NOTHING_CHANGED
            ));
        }
    }
}
