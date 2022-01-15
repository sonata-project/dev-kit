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
use App\Domain\Value\NextRelease;
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
        $projects = $this->projects->all();
        $releases = [];
        $apiRateLimitReachedWith = null;

        foreach ($projects as $project) {
            if ($project->package()->isAbandoned()) {
                continue;
            }

            foreach ($project->branches() as $branch) {
                if ($branch === $project->unstableBranch() && $project->isStable()) {
                    continue;
                }

                try {
                    $release = $this->determineNextRelease->__invoke($project, $branch);
                } catch (CannotDetermineNextRelease | NoPullRequestsMergedSinceLastRelease $e) {
                    continue;
                } catch (\RuntimeException $e) {
                    // API rate limit, we display what we can
                    $apiRateLimitReachedWith = $project->name();
                    break 2;
                }

                $releases[] = $release;
            }
        }

        usort($releases, static function (NextRelease $a, NextRelease $b): int {
            return \count($b->pullRequests()) <=> \count($a->pullRequests());
        });

        $content = $this->twig->render(
            'releases/overview.html.twig',
            [
                'projects' => $projects,
                'releases' => $releases,
                'api_rate_limit_reached_with' => $apiRateLimitReachedWith,
            ]
        );

        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
