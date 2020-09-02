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

namespace App\Github\Domain\Value;

use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Hook
{
    private int $id;
    private string $url;

    private function __construct(int $id, string $url)
    {
        Assert::greaterThan($id, 0);
        Assert::stringNotEmpty($url);

        $this->id = $id;
        $this->url = $url;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'id');

        Assert::keyExists($response, 'url');
        Assert::stringNotEmpty($response['url']);

        return new self(
            $response['id'],
            $response['url']
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function url(): string
    {
        return $this->url;
    }
}
