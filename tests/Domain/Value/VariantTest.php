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

namespace App\Tests\Domain\Value;

use App\Domain\Value\Variant;
use PHPUnit\Framework\TestCase;

final class VariantTest extends TestCase
{
    public function throwsExceptionIfPackageIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must not be empty!');

        Variant::fromValues('', '1.0');
    }

    public function throwsExceptionIfVersionIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Version must not be empty!');

        Variant::fromValues('sonata-project/dev-kit', '');
    }
}
