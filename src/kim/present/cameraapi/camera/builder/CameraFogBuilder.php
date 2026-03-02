<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\cameraapi\camera\builder;

use kim\present\cameraapi\session\CameraSession;
use kim\present\cameraapi\utils\VanillaFogIds;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;

/**
 * Builder for managing client-side Fog (Atmosphere) effects.
 *
 * Fog is managed as a stack similar to the vanilla `/fog` command. Each layer is
 * identified by a pair of (`fogId`, `userProvidedId`). The same `fogId` can be
 * pushed multiple times with different `userProvidedId` values. `remove()` removes
 * all layers that were pushed with the given `userProvidedId`. `send()` transmits
 * the current stack as a single PlayerFogPacket (only fog IDs are sent).
 *
 * For vanilla fog IDs use {@see VanillaFogIds}.
 *
 * Example:
 * ```php
 * use kim\present\cameraapi\utils\VanillaFogIds;
 *
 * $session->fog()
 *     ->push(VanillaFogIds::FOG_HELL)
 *     ->send();
 * ```
 */
final class CameraFogBuilder{

    /** @var list<array{fogId: string, userProvidedId: string}> */
    private array $stack = [];

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Pushes a fog layer onto the stack.
     *
     * @param string $fogId          Vanilla or resource pack fog ID (see {@see VanillaFogIds})
     * @param string $userProvidedId Unique identifier used later to remove this layer via {@see self::remove()}
     *
     * @return self
     */
    public function push(string $fogId, string $userProvidedId) : self{
        $this->stack[] = [
            'fogId' => $fogId,
            'userProvidedId' => $userProvidedId,
        ];
        return $this;
    }

    /**
     * Removes all fog layers that were pushed with the given userProvidedId.
     *
     * @param string $userProvidedId The identifier that was passed to {@see self::push()}
     *
     * @return self
     */
    public function remove(string $userProvidedId) : self{
        $this->stack = array_values(array_filter(
            $this->stack,
            static fn(array $entry) : bool => $entry['userProvidedId'] !== $userProvidedId
        ));
        return $this;
    }

    /**
     * Sends the configured fog stack as a packet.
     *
     * @return CameraSession Returns the session for method chaining.
     */
    public function send() : CameraSession{
        $fogLayers = array_map(
            static fn(array $entry) : string => $entry['fogId'],
            $this->stack
        );
        $this->session->sendPacket(PlayerFogPacket::create($fogLayers));
        return $this->session;
    }

}
