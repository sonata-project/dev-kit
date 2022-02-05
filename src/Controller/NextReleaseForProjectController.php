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

namespace App\Controller;

use App\Action\DetermineNextRelease;
use App\Action\Exception\NoPullRequestsMergedSinceLastRelease;
use App\Config\Exception\UnknownProject;
use App\Config\Projects;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class NextReleaseForProjectController
{
    private Projects $projects;
    private DetermineNextRelease $determineNextRelease;
    private Environment $twig;

    public function __construct(Projects $projects, DetermineNextRelease $determineNextRelease, Environment $twig)
    {
        $this->projects = $projects;
        $this->determineNextRelease = $determineNextRelease;
        $this->twig = $twig;
    }

    /**
     * @Route("/next-release/{projectName}/{branchName}", name="next_release_project")
     */
    public function __invoke(string $projectName, string $branchName): Response
    {
        try {
            $project = $this->projects->byName($projectName);
            $branch = $project->branch($branchName);
        } catch (UnknownProject $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        try {
            $release = $this->determineNextRelease->__invoke($project, $branch);
        } catch (NoPullRequestsMergedSinceLastRelease $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $content = $this->twig->render(
            'releases/project.html.twig',
            [
                'release' => $release,
            ]
        );

        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
