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
use App\Config\Exception\UnknownProject;
use App\Config\Projects;
use App\Domain\Value\NextRelease;
use App\Domain\Value\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class NextReleasesController extends AbstractController
{
    /**
     * @Route("/next-releases", name="next_releases_overview")
     */
    public function overview(Projects $projects, DetermineNextRelease $determineNextRelease): Response
    {
        $releases = array_reduce($projects->all(), static function (array $releases, Project $project) use ($determineNextRelease): array {
            try {
                $release = $determineNextRelease->__invoke($project);
            } catch (CannotDetermineNextRelease $e) {
                return $releases;
            }

            $releases[] = $release;

            return $releases;
        }, []);

        $response = $this->render(
            'releases/overview.html.twig',
            [
                'releases' => $releases,
            ]
        );

        // cache publicly for 3600 seconds
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }

    /**
     * @Route("/next-release/{project}", name="next_release_project")
     */
    public function nextRelease(string $project, Projects $projects, DetermineNextRelease $determineNextRelease): Response
    {
        try {
            $project = $projects->byName($project);
        } catch (UnknownProject $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $release = $determineNextRelease->__invoke($project);

        $response = $this->render(
            'releases/project.html.twig',
            [
                'release' => $release,
            ]
        );

        // cache publicly for 3600 seconds
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
