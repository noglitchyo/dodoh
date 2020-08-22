<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory;

use NoGlitchYo\Dealdoh\Entity\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;

/**
 * @codeCoverageIgnore
 */
interface MessageFactoryInterface
{
    /**
     * Create a new message.
     *
     * @param int  $id
     * @param bool $qr
     * @param int  $opcode
     * @param bool $isAa
     * @param bool $isTc
     * @param bool $isRd
     * @param bool $isRa
     * @param int  $z
     * @param int  $rcode
     *
     * @return \NoGlitchYo\Dealdoh\Entity\MessageInterface
     */
    public function create(
        int $id = null,
        bool $qr = false,
        int $opcode = HeaderInterface::RCODE_OK,
        bool $isAa = false,
        bool $isTc = false,
        bool $isRd = false,
        bool $isRa = false,
        int $z = 0,
        int $rcode = HeaderInterface::RCODE_OK
    ): MessageInterface;
}
