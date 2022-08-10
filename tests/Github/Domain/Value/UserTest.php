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

namespace App\Tests\Github\Domain\Value;

use App\Github\Domain\Value\User;
use App\Tests\Util\Factory\Github\Response\UserFactory;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    use Helper;

    public function testThrowsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([]);
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeyId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = UserFactory::create();
        unset($response['id']);

        User::fromResponse($response);
    }

    /**
     * @dataProvider \Ergebnis\Test\Util\DataProvider\IntProvider::zero()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\IntProvider::lessThanZero()
     */
    public function testThrowsExceptionIfIdIs(int $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = UserFactory::create([
            'id' => $value,
        ]);

        User::fromResponse($response);
    }

    public function testUsesIdFromResponse(): void
    {
        $response = UserFactory::create([
            'id' => $value = self::faker()->numberBetween(1, 999),
        ]);

        $user = User::fromResponse($response);

        static::assertSame(
            $value,
            $user->id()
        );
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeyLogin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = UserFactory::create();
        unset($response['login']);

        User::fromResponse($response);
    }

    /**
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function testThrowsExceptionIfLoginIs(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = UserFactory::create([
            'login' => $value,
        ]);

        User::fromResponse($response);
    }

    public function testUsesLoginFromResponse(): void
    {
        $response = UserFactory::create([
            'login' => $value = self::faker()->word(),
        ]);

        $user = User::fromResponse($response);

        static::assertSame(
            $value,
            $user->login()
        );
    }

    public function testUsesLoginForHandleFromResponse(): void
    {
        $response = UserFactory::create([
            'login' => $value = self::faker()->word(),
        ]);

        $user = User::fromResponse($response);

        static::assertSame(
            '@'.$value,
            $user->handle()
        );
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeyHtmlUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = UserFactory::create();
        unset($response['html_url']);

        User::fromResponse($response);
    }

    /**
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function testThrowsExceptionIfHtmlUrlIs(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = UserFactory::create([
            'html_url' => $value,
        ]);

        User::fromResponse($response);
    }

    public function testUsesHtmlurlFromResponse(): void
    {
        $response = UserFactory::create([
            'html_url' => $value = self::faker()->url(),
        ]);

        $user = User::fromResponse($response);

        static::assertSame(
            $value,
            $user->htmlUrl()
        );
    }
}
