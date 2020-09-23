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

namespace App\Domain\Value;

use App\Github\Domain\Value\IncomingWebhook\Payload;
use Packagist\Api\Result\Package;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Repository
{
    private string $username;
    private string $name;

    private function __construct(string $username, string $name)
    {
        $this->username = TrimmedNonEmptyString::fromString($username)->toString();
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
    }

    public static function fromPackage(Package $package): self
    {
        return self::fromUrl($package->getRepository());
    }

    public static function fromIncomingWebhookPayload(Payload $payload): self
    {
        $repository = $payload->repository();

        return self::fromValues(
            $repository->username(),
            $repository->name()
        );
    }

    public function username(): string
    {
        return $this->username;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function toString(): string
    {
        return u($this->username)
            ->append('/')
            ->append($this->name)
            ->toString();
    }

    private static function fromUrl(string $url): self
    {
        Assert::stringNotEmpty($url);
        Assert::contains($url, '/');
        Assert::startsWith($url, 'https://github.com/');

        list($username, $name) = u($url)
            ->replace('https://github.com/', '')
            ->replace('.git', '')
            ->split('/');

        return new self(
            $username->toString(),
            $name->toString()
        );
    }

    private static function fromValues(string $username, string $name): self
    {
        return new self($username, $name);
    }
}
