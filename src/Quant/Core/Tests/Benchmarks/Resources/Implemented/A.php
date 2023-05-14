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

namespace Quant\Core\Tests\Benchmarks\Resources\Implemented;

class A
{
    private string $aPublic = "aPublic";

    public function getAPublic(): string
    {
        return $this->aPublic;
    }

    public function setAPublic($s): A
    {
        $this->aPublic = $s;
        return $this;
    }

    private function getAPrivate(): string
    {
        return $this->aPrivate;
    }

    private function setAPrivate($s): A
    {
        $this->aPrivate = $s;
        return $this;
    }


    private function getAProtected(): string
    {
        return $this->aProtected;
    }

    private function setAProtected($s): A
    {
        $this->aProtected = $s;
        return $this;
    }

    private string $snafu;
    public function __construct(
        private ?string $aPrivate = null,
        private ?string $aProtected = null
    ) {
        $aPrivate !== null && $this->setAPrivate($aPrivate);
        $aProtected !== null && $this->setAProtected($aProtected);
    }


    public function proxySetAPrivate(string $s): A
    {
        return $this->setAPrivate($s);
    }

    public function proxyGetAPrivate(): string
    {
        return $this->getAPrivate();
    }
}
