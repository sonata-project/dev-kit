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

namespace App\Github\Domain\Value\Hook;

use App\Github\Domain\Value\Url;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Config
{
    /**
     * @var array<string, string>
     */
    private array $config;

    private Url $url;

    /**
     * @param array<string, string> $config
     */
    private function __construct(array $config)
    {
        Assert::notEmpty($config);

        $this->config = $config;
        $this->url = Url::fromString($config['url']);
    }

    /**
     * @param array<string, string> $config
     */
    public static function fromArray(array $config): self
    {
        Assert::notEmpty($config);

        return new self($config);
    }

    public function url(): Url
    {
        return $this->url;
    }

    /**
     * @param array<mixed> $config
     */
    public function equals(array $config): bool
    {
        return \count(array_diff_assoc($this->config, $config)) === 0;
    }
}
