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

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Hook
{
    private string $id;
    private string $url;

    private function __construct(string $url)
    {
        Assert::stringNotEmpty($url);

        $this->url = $url;
    }

    public static function fromConfigArray(array $config): self
    {
        Assert::notEmpty($config);
        Assert::keyExists($config, 'id');
        Assert::stringNotEmpty($config['id']);
        Assert::keyExists($config, 'url');
        Assert::stringNotEmpty($config['url']);

        return new self(
            $config['id'],
            $config['url']
        );
    }
    public function id(): string
    {
        return $this->id;
    }

    public function url(): string
    {
        return $this->url;
    }

}
