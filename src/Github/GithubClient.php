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

namespace App\Github;

use Github\Client;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class GithubClient extends Client
{
    /**
     * Adds a label from an issue if this one is not set.
     *
     * @param string $repoUser
     * @param string $repoName
     * @param int    $issueId
     * @param string $label
     */
    public function addIssueLabel($repoUser, $repoName, $issueId, $label): void
    {
        foreach ($this->issues()->labels()->all($repoUser, $repoName, $issueId) as $labelInfo) {
            if ($label === $labelInfo['name']) {
                return;
            }
        }

        $this->issues()->labels()->add($repoUser, $repoName, $issueId, $label);
    }

    /**
     * Removes a label from an issue if this one is set.
     *
     * @param string $repoUser
     * @param string $repoName
     * @param int    $issueId
     * @param string $label
     */
    public function removeIssueLabel($repoUser, $repoName, $issueId, $label): void
    {
        foreach ($this->issues()->labels()->all($repoUser, $repoName, $issueId) as $labelInfo) {
            if ($label === $labelInfo['name']) {
                $this->issues()->labels()->remove($repoUser, $repoName, $issueId, $label);

                break;
            }
        }
    }
}
