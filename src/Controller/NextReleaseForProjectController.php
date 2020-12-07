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
use App\Config\Exception\UnknownProject;
use App\Config\Projects;
use App\Domain\Value\Project;
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
     * @Route("/next-release/{projectName}", name="next_release_project")
     */
    public function __invoke(string $projectName): Response
    {
        try {
            $project = $this->projects->byName($projectName);
        } catch (UnknownProject $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $release = $this->determineNextRelease->__invoke($project);

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
