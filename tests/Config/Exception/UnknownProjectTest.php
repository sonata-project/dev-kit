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

namespace App\Tests\Config\Exception;

use App\Config\Exception\UnknownProject;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class UnknownProjectTest extends TestCase
{
    use Helper;

    public function testForName(): void
    {
        $name = self::faker()->word();

        $unknownProject = UnknownProject::forName($name);

        static::assertInstanceOf(
            \InvalidArgumentException::class,
            $unknownProject
        );
        static::assertSame(
            sprintf(
                'Could not find project with name "%s".',
                $name
            ),
            $unknownProject->getMessage()
        );
    }
}
