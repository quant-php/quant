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

namespace Quant\Core\Tests\Resources;

use Quant\Core\AbstractList;
use stdClass;

/**
 * @extends AbstractList<Entity>
 */
class EntityList extends AbstractList
{
    public function getType(): string
    {
        return Entity::class;
    }

    protected function compareItems(mixed $a, mixed $b): bool
    {
        if (!($a instanceof Entity) && !($b instanceof Entity)) {
            return false;
        }

        /**
         * @var Entity $a
         * @var Entity $b
         */
        return $a->getValue() === $b->getValue();
    }
}
