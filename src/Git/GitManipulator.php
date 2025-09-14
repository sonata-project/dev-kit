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

namespace App\Git;

use App\Command\AbstractCommand;
use App\Domain\Value\Branch;
use App\Domain\Value\Project;
use Gitonomy\Git\Admin;
use Gitonomy\Git\Repository;
use Symfony\Component\Filesystem\Filesystem;

use function Symfony\Component\String\u;

final class GitManipulator
{
    public function __construct(
        private Filesystem $filesystem,
        private string $githubToken,
    ) {
    }

    public function gitCloneProject(Project $project): Repository
    {
        $repository = $project->repository();

        $clonePath = \sprintf(
            '%s/%s',
            sys_get_temp_dir(),
            $repository->toString()
        );

        if ($this->filesystem->exists($clonePath)) {
            $this->filesystem->remove($clonePath);
        }

        $repository = Admin::cloneRepository(
            $clonePath,
            \sprintf(
                'https://%s:%s@github.com/%s/%s',
                $repository->username(),
                $this->githubToken,
                $repository->username(),
                $repository->name()
            )
        );

        $repository->run('config', ['--local', 'user.name', AbstractCommand::GITHUB_USER]);
        $repository->run('config', ['--local', 'user.email', AbstractCommand::GITHUB_EMAIL]);

        return $repository;
    }

    public function prepareBranch(Repository $repository, Branch $branch, string $suffix = '-dev-kit'): string
    {
        $devKitBranchName = u($branch->name())->append($suffix)->toString();

        $repository->run('reset', ['--hard']);

        // Checkout the targeted branch
        if ($repository->getReferences()->hasBranch($branch->name())) {
            $repository->run('checkout', [$branch->name()]);
        } else {
            $repository->run('checkout', ['-b', $branch->name(), '--track', \sprintf('origin/%s', $branch->name())]);
        }

        // Checkout the dev-kit branch
        if ($repository->getReferences()->hasRemoteBranch(\sprintf('origin/%s', $devKitBranchName))) {
            $repository->run('checkout', ['-b', $devKitBranchName, '--track', \sprintf('origin/%s', $devKitBranchName)]);
        } else {
            $repository->run('checkout', ['-b', $devKitBranchName]);
        }

        return $devKitBranchName;
    }
}
