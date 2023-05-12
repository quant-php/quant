<?php

/**
 * This file is part of the quant project.
 *
 * (c) 2023 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

namespace Quant\Core\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Quant\Core\Attribute\Accessor;
use Quant\Core\Lang\Modifier;

class AccessorTest extends TestCase
{
    public function testAccessor(): void
    {
        $this->assertSame(
            Modifier::PUBLIC,
            $this->createAccessor()->getAccessLevel()
        );
        $this->assertSame(
            Modifier::PROTECTED,
            $this->createAccessor(Modifier::PROTECTED)->getAccessLevel()
        );
    }

    protected function createAccessor(Modifier $modifier = null): Accessor
    {
        return $modifier ? new class ($modifier) extends Accessor{
        } : new class () extends Accessor{
        };
    }
}
