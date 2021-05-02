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

    /**
     * @test
     */
    public function forName()
    {
        $name = self::faker()->word();

        $unknownProject = UnknownProject::forName($name);

        self::assertInstanceOf(
            \InvalidArgumentException::class,
            $unknownProject
        );
        self::assertSame(
            sprintf(
                'Could not find Project with name "%s".',
                $name
            ),
            $unknownProject->getMessage()
        );
    }
}
