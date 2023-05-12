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

namespace Quant\Core\Attribute;

use Quant\Core\Lang\Modifier;

abstract class Accessor
{
    public function __construct(private readonly Modifier $accessLevel = Modifier::PUBLIC)
    {
    }

    public function getAccessLevel(): Modifier
    {
        return $this->accessLevel;
    }
}
