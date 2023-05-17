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

namespace Quant\PHPStan\Tests\Data;

use Quant\Core\Attribute\Getter;
use Quant\Core\Lang\Modifier;
use Quant\Core\Trait\AccessorTrait;

class C
{
    use AccessorTrait;

    #[Getter(Modifier::PROTECTED)]
    private string $protectedBarC;
}
