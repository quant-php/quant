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

namespace Quant\Core\Tests\Trait\Resources;

/**
 *
 */
class C extends B
{
    protected function applyProtectedGuard(string $f): string
    {
        return "protected guard";
    }

    protected function proxyApplyPublicGuard(): string
    {
        $b = new B("");
        return $b->applyPublicGuard("");
    }

    public function proxyAProtectedVar(): A
    {
        $b = new B("");
        return $b->setProtectedVar($b->applyProtectedGuard("k"));
    }
}
