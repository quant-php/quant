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

namespace Quant\Core\Lang;

use ReflectionProperty;

enum Modifier: int
{
    case PRIVATE = ReflectionProperty::IS_PRIVATE;

    case PROTECTED = ReflectionProperty::IS_PROTECTED;

    case PUBLIC = ReflectionProperty::IS_PUBLIC;
}
