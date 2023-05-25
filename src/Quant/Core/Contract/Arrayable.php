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

namespace Quant\Core\Contract;

interface Arrayable
{
    /**
     * Returns an array representation of an instance of this class.
     *
     * @return array<int, mixed>
     */
    public function toArray(): array;
}
