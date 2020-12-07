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

use App\Github\Domain\Value\Hook\Config;
use App\Github\Domain\Value\Hook\Events;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Hook
{
    private int $id;
    private Url $url;
    private bool $active;
    private Config $config;
    private Events $events;

    private function __construct(int $id, Url $url, bool $active, Config $config, Events $events)
    {
        Assert::greaterThan($id, 0);

        $this->id = $id;
        $this->url = $url;
        $this->active = $active;
        $this->config = $config;
        $this->events = $events;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'id');

        Assert::keyExists($response, 'url');

        Assert::keyExists($response, 'active');
        Assert::boolean($response['active']);

        Assert::keyExists($response, 'config');

        Assert::keyExists($response, 'events');

        return new self(
            $response['id'],
            Url::fromString($response['url']),
            $response['active'],
            Config::fromArray($response['config']),
            Events::fromArray($response['events'])
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function events(): Events
    {
        return $this->events;
    }
}
