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

namespace kim\present\cameraapi\builder;

use kim\present\cameraapi\session\CameraSession;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;

/**
 * Builder for managing client-side Fog (Atmosphere) effects.
 *
 * Fog is managed as a stack: push adds layers by fog ID, remove removes by fog ID.
 * send() transmits the current stack as a single PlayerFogPacket.
 *
 * Example:
 * ```php
 * $session->fog()
 *     ->push("minecraft:fog_hell")
 *     ->send();
 * ```
 */
final class CameraFogBuilder{

    /** @var list<string> */
    private array $stack = [];

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Adds a fog layer to the stack (push).
     *
     * @param string $fogId Vanilla or resource pack fog ID (e.g. "minecraft:fog_crimson_forest")
     *
     * @return self
     */
    public function push(string $fogId) : self{
        $this->stack[$fogId] = $fogId;
        return $this;
    }

    /**
     * Removes all fog layers with the given fog ID from the stack.
     *
     * @param string $fogId The fog ID to remove (same as used in push)
     *
     * @return self
     */
    public function remove(string $fogId) : self{
        unset($this->stack[$fogId]);
        return $this;
    }

    /**
     * Sends the configured fog stack as a packet.
     *
     * @return CameraSession Returns the session for method chaining.
     */
    public function send() : CameraSession{
        $this->session->sendPacket(PlayerFogPacket::create(array_values($this->stack)));
        return $this->session;
    }

}
