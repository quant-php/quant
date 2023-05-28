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

namespace Quant\Core\Tests;

use PHPUnit\Framework\TestCase;
use Quant\Core\Tests\Contract\Resources\Money;

class ComparableTest extends TestCase
{
    public function testComparable(): void
    {
        $tests = [
            [[10, 40], [10, 20], 1],
            [[10, 40], [10, 40], 0],
            [[10, 20], [10, 40], -1],
            [[9, 20], [8, 40], 1],
            [[3, 20], [8, 40], -1],
        ];

        foreach ($tests as $i => $test) {
            $aAmount = $test[0][0];
            $aCents = $test[0][1];
            $bAmount = $test[1][0];
            $bCents = $test[1][1];
            $result = $test[2];

            $moneyA = $this->getComparableClass($aAmount, $aCents);
            $moneyB = $this->getComparableClass($bAmount, $bCents);

            $this->assertSame($result, $moneyA->compareTo($moneyB));
        }
    }


    protected function getComparableClass(int $amount, int $cent): Money
    {
        return new Money($amount, $cent);
    }
}
