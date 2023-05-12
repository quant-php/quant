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
use Quant\Core\Attribute\Getter;

class GetterTest extends TestCase
{
    public function testGetter(): void
    {
        $this->assertInstanceOf(Accessor::class, new Getter());
    }
}
