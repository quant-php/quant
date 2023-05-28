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

namespace Quant\Core\Tests\Contract\Resources;

use Quant\Core\Attribute\Getter;
use Quant\Core\Contract\Comparable;
use Quant\Core\Trait\AccessorTrait;

class Money implements Comparable
{
    use AccessorTrait;

    public function __construct(
        #[Getter]
        private int $amount,
        #[Getter]
        private int $cents
    ) {
    }

    public function compareTo(Comparable $target): int
    {
        if (!($target instanceof Money)) {
            return 1;
        }

        $aAmount = $this->getAmount();
        $bAmount = $target->getAmount();

        $aCents = $this->getCents();
        $bCents = $target->getCents();


        $c = ($aAmount === $bAmount ? 0 : ($aAmount < $bAmount ? -1 : 1));

        return $c !== 0 ?  $c : ($aCents < $bCents ? -1 : ($aCents > $bCents ? 1 : 0));
    }
}
