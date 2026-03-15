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

    public function __construct(
        private readonly CameraSession $session
    ){
        $this->presetId = 'minecraft:aim_assist_default';
        $this->viewAngle = new Vector2(90.0, 90.0);
        $this->distance = 16.0;
        $this->targetMode = CameraAimAssistTargetMode::ANGLE;
    }

    public function preset(?string $presetId) : self{
        $this->presetId = $presetId;
        return $this;
    }

    public function viewAngle(float $horizontal, float $vertical) : self{
        $this->viewAngle = new Vector2($horizontal, $vertical);
        return $this;
    }

    public function distance(float $distance) : self{
        $this->distance = $distance;
        return $this;
    }

    public function targetMode(CameraAimAssistTargetMode $mode) : self{
        $this->targetMode = $mode;
        return $this;
    }

    public function debug(bool $showDebugRender = true) : self{
        $this->showDebugRender = $showDebugRender;
        return $this;
    }

    public function send() : CameraSession{
        $player = $this->session->getPlayer();
        if($player === null || !$player->isConnected()){
            return $this->session;
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

