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
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchWebhooksCommand extends AbstractNeedApplyCommand
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
    private GithubClient $github;
    private string $devKitToken;

    public function __construct(Projects $projects, GithubClient $github, string $devKitToken)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->github = $github;

        Assert::stringNotEmpty($devKitToken, '$devKitToken must not be an empty string!');
        $this->devKitToken = $devKitToken;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:webhooks')
            ->setDescription('Dispatches webhooks for all sonata projects.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch webhooks for all sonata projects');

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

        $this->io->section('DevKit hook');

        $hookBaseUrl = 'https://d5zda2diva-x6miu6vkqhzpi.eu.s5y.io/github';
        $hookCompleteUrl = sprintf(
            '%s?%s',
            $hookBaseUrl,
            http_build_query([
                'token' => $this->devKitToken,
            ])
        );

        // Set hook configs
        $config = [
            'url' => $hookCompleteUrl,
            'insecure_ssl' => '0',
            'content_type' => 'json',
        ];
        $events = [
            'issue_comment',
            'pull_request',
            'pull_request_review_comment',
        ];

        $configuredHooks = $this->github->repo()->hooks()->all(
            $repository->vendor(),
            $repository->name()
        );

        // First, check if the hook exists.
        $devKitHook = null;
        foreach ($configuredHooks as $hook) {
            if (\array_key_exists('url', $hook['config'])
                && 0 === strncmp($hook['config']['url'], $hookBaseUrl, \strlen($hookBaseUrl))) {
                $devKitHook = $hook;

                break;
            }
        }

        if (!$devKitHook) {
            $this->io->comment('Has to be created.');

            if ($this->apply) {
                $this->github->repo()->hooks()->create($repository->vendor(), $repository->name(), [
                    'name' => 'web',
                    'config' => $config,
                    'events' => $events,
                    'active' => true,
                ]);
                $this->io->success('Hook created.');
            }
        } elseif (\count(array_diff_assoc($devKitHook['config'], $config))
            || \count(array_diff($devKitHook['events'], $events))
            || !$devKitHook['active']
        ) {
            $this->io->comment('Has to be updated.');

            if ($this->apply) {
                $this->github->repo()->hooks()->update($repository->vendor(), $repository->name(), $devKitHook['id'], [
                    'name' => 'web',
                    'config' => $config,
                    'events' => $events,
                    'active' => true,
                ]);
                $this->github->repo()->hooks()->ping($repository->vendor(), $repository->name(), $devKitHook['id']);
                $this->io->success('Hook updated.');
            }
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    private function deleteHooks(Project $project): void
    {
        $repository = $project->repository();

        $this->io->section('Check Hooks to be deleted');

        $configuredHooks = $this->github->repo()->hooks()->all($repository->vendor(), $repository->name());

        // Check if hook should be deleted.
        foreach ($configuredHooks as $key => $hook) {
            foreach (self::HOOK_URLS_TO_BE_DELETED as $url) {
                $currentHookUrl = $hook['config']['url'];

                if (u($currentHookUrl)->startsWith($url)) {
                    $this->io->comment(sprintf(
                        'Hook "%s" will be deleted',
                        $currentHookUrl
                    ));

                    if ($this->apply) {
                        $this->github->repo()->hooks()->remove($repository->vendor(), $repository->name(), $hook['id']);

                        $this->io->success(sprintf(
                            'Hook "%s" deleted.',
                            $currentHookUrl
                        ));
                    }
                }
            }
        }
    }
}
