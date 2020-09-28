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
use App\Action\Exception\CannotDetermineNextRelease;
use App\Action\Exception\NoPullRequestsMergedSinceLastRelease;
use App\Config\Projects;
use App\Domain\Value\Project;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class NextReleaseOverviewController
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
     * @Route("/next-releases", name="next_releases_overview")
     */
    public function __invoke(): Response
    {
        $releases = array_reduce($this->projects->all(), function (array $releases, Project $project): array {
            try {
                $release = $this->determineNextRelease->__invoke($project);
            } catch (CannotDetermineNextRelease | NoPullRequestsMergedSinceLastRelease $e) {
                return $releases;
            }

            $releases[] = $release;

            return $releases;
        }, []);

        $content = $this->twig->render(
            'releases/overview.html.twig',
            [
                'releases' => $releases,
            ]
        );

        $response = new Response();
        $response->setContent($content);
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
