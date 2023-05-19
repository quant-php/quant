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

use Quant\Core\Lang\Modifier;
use Quant\Core\Trait\AccessorTrait;
use Quant\Core\Attribute\Getter;
use Quant\Core\Attribute\Setter;

#[Getter]
#[Setter]
class ClassHasAttributes
{
    use AccessorTrait;

    #[Setter]
    public string $foobar = "Ok";

    #[Getter(Modifier::PRIVATE)]
    private string $snafu;

    public function __construct(
        #[Setter] #[Getter]
        public string $foo,
        protected string $bar,
    ) {
    }
}
