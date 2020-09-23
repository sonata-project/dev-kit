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

namespace App\Github\Domain\Value\PullRequest;

use App\Domain\Value\TrimmedNonEmptyString;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class User
{
    private string $login;
    private string $htmlUrl;

    private function __construct(string $login, string $htmlUrl)
    {
        $this->login = TrimmedNonEmptyString::fromString($login)->toString();
        $this->htmlUrl = TrimmedNonEmptyString::fromString($htmlUrl)->toString();
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'login');
        Assert::stringNotEmpty($response['login']);

        Assert::keyExists($response, 'html_url');
        Assert::stringNotEmpty($response['html_url']);

        return new self(
            $response['login'],
            $response['html_url']
        );
    }

    public function login(): string
    {
        return $this->login;
    }

    public function htmlUrl(): string
    {
        return $this->htmlUrl;
    }
}
