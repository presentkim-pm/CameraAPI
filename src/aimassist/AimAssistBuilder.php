<?php

declare(strict_types=1);

namespace kim\present\cameraapi\aimassist;

use kim\present\cameraapi\session\CameraSession;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\CameraAimAssistPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistActionType;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistTargetMode;

final class AimAssistBuilder{

    private ?string $presetId;
    private Vector2 $viewAngle;
    private float $distance;
    private CameraAimAssistTargetMode $targetMode;
    private bool $showDebugRender = false;

    /** Smallest distance accepted by the client. */
    public const MIN_DISTANCE = 1.0;
    /** Largest distance accepted by the client. */
    public const MAX_DISTANCE = 16.0;

    public function __construct(
        private readonly CameraSession $session
    ){
        $this->presetId = 'minecraft:aim_assist_default';
        $this->viewAngle = new Vector2(90.0, 90.0);
        $this->distance = 16.0;
        $this->targetMode = CameraAimAssistTargetMode::ANGLE;
    }

    /**
     * Sets the aim assist preset to activate.
     *
     * The preset has to be registered in {@see AimAssistPresetRegistry} (checked in {@see self::send()}).
     * Passing null or an empty string makes {@see self::send()} clear the active aim assist instead, like
     * {@see self::clear()}.
     *
     * @param string|null $presetId Preset identifier, e.g. {@see DefaultAimAssistPresetIds::MINECRAFT_DEFAULT}.
     *
     * @return self
     */
    public function preset(?string $presetId) : self{
        $this->presetId = $presetId;
        return $this;
    }

    /**
     * Makes {@see self::send()} clear the active aim assist for the player.
     *
     * @return self
     */
    public function clear() : self{
        $this->presetId = null;
        return $this;
    }

    /**
     * Sets the size of the area in which targets are considered, in degrees.
     *
     * @param float $horizontal Horizontal angle, must be finite and greater than 0.
     * @param float $vertical   Vertical angle, must be finite and greater than 0.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If an angle is not a finite number greater than 0.
     */
    public function viewAngle(float $horizontal, float $vertical) : self{
        foreach(['horizontal' => $horizontal, 'vertical' => $vertical] as $name => $angle){
            if(!is_finite($angle) || $angle <= 0.0){
                throw new \InvalidArgumentException("Aim assist $name view angle must be greater than 0, got $angle");
            }
        }
        $this->viewAngle = new Vector2($horizontal, $vertical);
        return $this;
    }

    /**
     * Sets the maximum distance (in blocks) at which targets are considered.
     *
     * @param float $distance Distance between {@see self::MIN_DISTANCE} and {@see self::MAX_DISTANCE}.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the distance is outside the range supported by the client.
     */
    public function distance(float $distance) : self{
        if(!is_finite($distance) || $distance < self::MIN_DISTANCE || $distance > self::MAX_DISTANCE){
            throw new \InvalidArgumentException(
                "Aim assist distance must be between " . self::MIN_DISTANCE . " and " . self::MAX_DISTANCE .
                ", got $distance"
            );
        }
        $this->distance = $distance;
        return $this;
    }

    /**
     * Sets how targets are selected (by angle or by distance).
     *
     * @param CameraAimAssistTargetMode $mode
     *
     * @return self
     */
    public function targetMode(CameraAimAssistTargetMode $mode) : self{
        $this->targetMode = $mode;
        return $this;
    }

    /**
     * Shows the client's debug rendering of the aim assist area.
     *
     * @param bool $showDebugRender
     *
     * @return self
     */
    public function debug(bool $showDebugRender = true) : self{
        $this->showDebugRender = $showDebugRender;
        return $this;
    }

    /**
     * Sends the aim assist instruction: activates the configured preset, or clears the active one when no preset
     * is set. Does nothing if the player is offline.
     *
     * @return CameraSession Returns the session for method chaining.
     *
     * @throws \InvalidArgumentException If the preset is not registered in {@see AimAssistPresetRegistry}.
     */
    public function send() : CameraSession{
        $player = $this->session->getPlayer();
        if($player === null || !$player->isConnected()){
            return $this->session;
        }

        if($this->presetId !== null && $this->presetId !== '' &&
            AimAssistPresetRegistry::getPreset($this->presetId) === null){
            throw new \InvalidArgumentException("Unknown aim assist preset: " . $this->presetId);
        }

        if($this->presetId === null || $this->presetId === ''){
            $packet = CameraAimAssistPacket::create(
                '',
                $this->viewAngle,
                $this->distance,
                $this->targetMode,
                CameraAimAssistActionType::CLEAR,
                $this->showDebugRender
            );
        }else{
            $packet = CameraAimAssistPacket::create(
                $this->presetId,
                $this->viewAngle,
                $this->distance,
                $this->targetMode,
                CameraAimAssistActionType::SET,
                $this->showDebugRender
            );
        }

        $player->getNetworkSession()->sendDataPacket($packet);
        return $this->session;
    }
}

