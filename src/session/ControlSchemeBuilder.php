<?php

declare(strict_types=1);

namespace kim\present\cameraapi\session;

use kim\present\cameraapi\utils\ControlSchemePackets;
use pocketmine\network\mcpe\protocol\ClientboundControlSchemeSetPacket;

final class ControlSchemeBuilder{

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Sends the given control scheme packet and returns the session for chaining.
     */
    public function send(ClientboundControlSchemeSetPacket $packet) : CameraSession{
        return $this->session->sendPacket($packet);
    }

    /**
     * Sends a control scheme by name from {@see ControlSchemePackets} (case-insensitive).
     *
     * @throws \InvalidArgumentException
     */
    public function byName(string $schemeName) : CameraSession{
        $map = ControlSchemePackets::getAll();
        $upper = mb_strtoupper($schemeName);

        if(!isset($map[$upper])){
            throw new \InvalidArgumentException("Unknown control scheme: " . $schemeName);
        }

        return $this->send($map[$upper]);
    }

    public function lockedPlayerRelativeStrafe() : CameraSession{
        return $this->send(ControlSchemePackets::LOCKED_PLAYER_RELATIVE_STRAFE());
    }

    /**
     * Camera-relative movement.
     *
     * Note: This scheme typically requires an orbit/boom-style camera preset to have a visible effect on the client,
     * such as "minecraft:follow_orbit" or "minecraft:fixed_boom".
     */
    public function cameraRelative() : CameraSession{
        return $this->send(ControlSchemePackets::CAMERA_RELATIVE());
    }

    /**
     * Camera-relative movement with strafe.
     *
     * Note: This scheme typically requires an orbit/boom-style camera preset to have a visible effect on the client,
     * such as "minecraft:follow_orbit" or "minecraft:fixed_boom".
     */
    public function cameraRelativeStrafe() : CameraSession{
        return $this->send(ControlSchemePackets::CAMERA_RELATIVE_STRAFE());
    }

    /**
     * Player-relative movement.
     *
     * Note: This scheme typically requires "minecraft:fixed_boom" to have a visible effect on the client.
     */
    public function playerRelative() : CameraSession{
        return $this->send(ControlSchemePackets::PLAYER_RELATIVE());
    }

    /**
     * Player-relative movement with strafe.
     *
     * Note: This scheme typically requires "minecraft:fixed_boom" to have a visible effect on the client.
     */
    public function playerRelativeStrafe() : CameraSession{
        return $this->send(ControlSchemePackets::PLAYER_RELATIVE_STRAFE());
    }
}

