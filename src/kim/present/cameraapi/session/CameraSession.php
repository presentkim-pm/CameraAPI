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

namespace kim\present\cameraapi\session;

use kim\present\cameraapi\camera\builder\CameraFadeBuilder;
use kim\present\cameraapi\camera\builder\CameraFogBuilder;
use kim\present\cameraapi\camera\builder\CameraFovBuilder;
use kim\present\cameraapi\camera\builder\CameraSetBuilder;
use kim\present\cameraapi\camera\builder\CameraSplineBuilder;
use kim\present\cameraapi\camera\builder\CameraTargetBuilder;
use kim\present\cameraapi\aimassist\AimAssistActorPriorityBuilder;
use kim\present\cameraapi\aimassist\AimAssistBuilder;
use kim\present\cameraapi\hud\HudPreset;
use kim\present\cameraapi\hud\HudPresetRegistry;
use kim\present\cameraapi\timeline\CameraTimeline;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\ClientboundControlSchemeSetPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraFovInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraTargetInstruction;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;

/**
 * Manages the camera state and operations for a single player.
 *
 * This class provides a fluent interface to access various camera builders
 * and execute camera instructions.
 */
final class CameraSession{

    /** @var TaskHandler[] */
    private array $activeTasks = [];
    private \WeakReference $playerRef;
    private ?string $waitingSignal = null;
    /** @var array<int, array{float, \Closure|string}> */
    private array $pausedTimelineQueue = [];
    private ?CameraTimeline $pausedTimeline = null;
    /** @var list<array{fogId: string, userProvidedId: string}> */
    private array $fogStack = [];
    private ?bool $clientAimAssistAllowed = null;

    // Client state as last sent through this session (see reapply())
    private ?CameraSetInstruction $currentSet = null;
    private ?CameraFovInstruction $currentFov = null;
    private ?CameraTargetInstruction $currentTarget = null;
    private ?int $attachedEntityId = null;
    private ?HudPreset $currentHud = null;
    private ?ClientboundControlSchemeSetPacket $currentControlScheme = null;

    /**
     * @param Player $player The player associated with this session.
     */
    public function __construct(Player $player){
        $this->playerRef = \WeakReference::create($player);
    }

    /**
     * Retrieves the player object if they are still online.
     *
     * @return Player|null The player instance or null if offline.
     */
    public function getPlayer() : ?Player{
        return $this->playerRef->get();
    }

    /**
     * Creates a builder for 'Camera Set' instruction.
     * Used to position and rotate the camera.
     *
     * @return CameraSetBuilder
     */
    public function set() : CameraSetBuilder{
        return new CameraSetBuilder($this);
    }

    /**
     * Creates a builder for 'Camera Fade' instruction.
     * Used to control screen fading (color, time).
     *
     * @return CameraFadeBuilder
     */
    public function fade() : CameraFadeBuilder{
        return new CameraFadeBuilder($this);
    }

    /**
     * Creates a builder for 'Camera Target' instruction.
     * Used to make the camera track an entity or position.
     *
     * @return CameraTargetBuilder
     */
    public function target() : CameraTargetBuilder{
        return new CameraTargetBuilder($this);
    }

    /**
     * Creates a builder for 'Camera FOV' instruction.
     * Used to change the Field of View.
     *
     * @return CameraFovBuilder
     */
    public function fov() : CameraFovBuilder{
        return new CameraFovBuilder($this);
    }

    /**
     * Creates a builder for managing client-side Fog effects.
     * Used to push or remove fog settings.
     *
     * @return CameraFogBuilder
     */
    public function fog() : CameraFogBuilder{
        return new CameraFogBuilder($this);
    }

    /**
     * Returns the fog stack last sent to the client through {@see CameraFogBuilder::send()}.
     *
     * @return list<array{fogId: string, userProvidedId: string}>
     */
    public function getFogStack() : array{
        return $this->fogStack;
    }

    /**
     * @param list<array{fogId: string, userProvidedId: string}> $fogStack
     *
     * @internal Called by {@see CameraFogBuilder::send()}.
     */
    public function setFogStack(array $fogStack) : void{
        $this->fogStack = $fogStack;
    }

    /**
     * Creates a control scheme builder for this session's player.
     *
     * This replaces the older direct packet API and provides a fluent entry point:
     * `Camera::of($player)->controlScheme()->cameraRelative()`.
     */
    public function controlScheme() : ControlSchemeBuilder{
        return new ControlSchemeBuilder($this);
    }

    /**
     * Attaches the camera to the given entity or runtime ID (e.g. POV spectator – see through the entity's eyes).
     * The client will use the entity's position and rotation for the camera until detached.
     *
     * Note: For the attachment to have visible effect, the active camera preset must support entity attachment,
     * such as "minecraft:follow_orbit" or "minecraft:fixed_boom".
     *
     * @param Entity|int $entityOrRuntimeId The entity to attach to (uses its runtime ID), or the entity runtime ID
     *                                      directly.
     *
     * @return self
     */
    public function attachToEntity(Entity|int $entityOrRuntimeId) : self{
        $runtimeId = $entityOrRuntimeId instanceof Entity ? $entityOrRuntimeId->getId() : $entityOrRuntimeId;
        return $this->sendPacket(CameraInstructionPacket::create(
            set: null,
            clear: null,
            fade: null,
            target: null,
            removeTarget: null,
            fieldOfView: null,
            spline: null,
            attachToEntity: $runtimeId,
            detachFromEntity: null
        ));
    }

    /**
     * Detaches the camera from the currently attached entity and returns to normal control.
     *
     * @return self
     */
    public function detachFromEntity() : self{
        return $this->sendPacket(CameraInstructionPacket::create(
            set: null,
            clear: null,
            fade: null,
            target: null,
            removeTarget: null,
            fieldOfView: null,
            spline: null,
            attachToEntity: null,
            detachFromEntity: true
        ));
    }

    /**
     * Applies a HUD preset to this session's player.
     *
     * Accepts either a {@see HudPreset} instance or a string name of a preset
     * registered in {@see HudPresetRegistry}. If a name is given and no preset
     * is found, throws {@see \InvalidArgumentException}.
     *
     * @param HudPreset|string $preset       A preset instance or a registry key (e.g.
     *                                       {@see HudPresetRegistry::PRESET_CLEAR}).
     *
     * @return self
     *
     * @throws \InvalidArgumentException When a string name is passed and no preset is registered under that name.
     */
    public function hud(HudPreset|string $preset) : self{
        if($preset instanceof HudPreset){
            $preset->send($this);
            return $this;
        }

        if(!HudPresetRegistry::isRegistered($preset)){
            throw new \InvalidArgumentException("Unknown HUD preset: " . $preset);
        }

        HudPresetRegistry::get($preset)->send($this);
        return $this;
    }

    /**
     * Creates an aim-assist builder that sends {@see CameraAimAssistPacket} to this session's player.
     *
     * The builder controls activation of a specific aim-assist preset (identifier), along with view angle, distance,
     * target mode and action type.
     */
    public function aimAssist() : AimAssistBuilder{
        return new AimAssistBuilder($this);
    }

    /**
     * Creates a builder that overrides the aim assist priority of individual actors.
     */
    public function aimAssistActorPriority() : AimAssistActorPriorityBuilder{
        return new AimAssistActorPriorityBuilder($this);
    }

    /**
     * Whether the client allows aim assist, as last reported by the client; null if it has not reported yet.
     */
    public function isClientAimAssistAllowed() : ?bool{
        return $this->clientAimAssistAllowed;
    }

    /**
     * @internal Called when the client reports a change of its aim assist setting.
     */
    public function setClientAimAssistAllowed(bool $allowed) : void{
        $this->clientAimAssistAllowed = $allowed;
    }

    /**
     * Creates a builder for 'Camera Spline' instruction.
     * Used to create smooth cinematic camera paths.
     *
     * @return CameraSplineBuilder
     * @deprecated Causes a fatal issue where the client drops the connection due to an internal error. Do not use
     *              until further notice.
     */
    public function spline() : CameraSplineBuilder{
        return new CameraSplineBuilder($this);
    }

    /**
     * Sends a camera shake packet to the player.
     *
     * @param float $intensity Intensity of the shake (0.0 - 1.0 recommended).
     * @param float $duration  Duration in seconds.
     * @param int   $type      The type of shake (positional or rotational).
     *
     * @return self
     */
    public function shake(
        float $intensity = 0.5,
        float $duration = 1.0,
        int $type = CameraShakePacket::TYPE_POSITIONAL
    ) : self{
        return $this->sendPacket(CameraShakePacket::create(
            $intensity,
            $duration,
            $type,
            CameraShakePacket::ACTION_ADD
        ));
    }

    /**
     * Stops any active camera shake.
     *
     * @param int $type The type of shake to stop.
     *
     * @return self
     */
    public function stopShake(int $type = CameraShakePacket::TYPE_POSITIONAL) : self{
        return $this->sendPacket(CameraShakePacket::create(
            0.0,
            0.0,
            $type,
            CameraShakePacket::ACTION_STOP
        ));
    }

    /**
     * Clears all camera instructions and resets to the default view.
     *
     * @return self
     */
    public function clear() : self{
        return $this->sendPacket(CameraInstructionPacket::create(
            set: null,
            clear: true,
            fade: null,
            target: null,
            removeTarget: null,
            fieldOfView: null,
            spline: null,
            attachToEntity: null,
            detachFromEntity: null
        ));
    }

    /**
     * Stops all timeline tasks and restores the client's camera, fog and HUD to their defaults.
     *
     * Used when the plugin is disabled, so players are not left with a frozen camera, leftover fog layers or a
     * hidden HUD once nothing is controlling them anymore.
     *
     * @return self
     */
    public function reset() : self{
        $this->stop();
        $this->clear();
        $this->detachFromEntity();

        if($this->fogStack !== []){
            $this->fogStack = [];
            $this->sendPacket(PlayerFogPacket::create([]));
        }

        // Not looked up through HudPresetRegistry, since built-in names can be overwritten there
        (new HudPreset())->send($this);
        return $this;
    }

    /**
     * Sends a packet to the player if they are online.
     *
     * @param ClientboundPacket $pk The packet to send (e.g. CameraInstructionPacket, CameraShakePacket).
     *
     * @return self
     */
    public function sendPacket(ClientboundPacket $pk) : self{
        $player = $this->getPlayer();
        if($player !== null && $player->isConnected()){
            $player->getNetworkSession()->sendDataPacket($pk);
            $this->track($pk);
        }
        return $this;
    }

    /**
     * Remembers the client state a packet sent through this session changes.
     */
    private function track(ClientboundPacket $pk) : void{
        if($pk instanceof ClientboundControlSchemeSetPacket){
            $this->currentControlScheme = $pk;
            return;
        }
        if(!$pk instanceof CameraInstructionPacket){
            return;
        }

        if($pk->getClear() === true){
            $this->currentSet = null;
            $this->currentFov = null;
            $this->currentTarget = null;
        }
        if($pk->getSet() !== null){
            $this->currentSet = $pk->getSet();
        }
        $fov = $pk->getFieldOfView();
        if($fov !== null){
            $this->currentFov = $fov->getClear() ? null : $fov;
        }
        if($pk->getTarget() !== null){
            $this->currentTarget = $pk->getTarget();
        }
        if($pk->getRemoveTarget() === true){
            $this->currentTarget = null;
        }
        if($pk->getAttachToEntity() !== null){
            $this->attachedEntityId = $pk->getAttachToEntity();
        }
        if($pk->getDetachFromEntity() === true){
            $this->attachedEntityId = null;
        }
    }

    /**
     * Returns the camera set instruction that is currently active on the client, or null if there is none
     * (nothing was set yet, or the camera was cleared).
     */
    public function getCurrentSet() : ?CameraSetInstruction{
        return $this->currentSet;
    }

    /**
     * Returns the FOV instruction that is currently active on the client, or null if there is none.
     */
    public function getCurrentFov() : ?CameraFovInstruction{
        return $this->currentFov;
    }

    /**
     * Returns the target instruction that is currently active on the client, or null if there is none.
     */
    public function getCurrentTarget() : ?CameraTargetInstruction{
        return $this->currentTarget;
    }

    /**
     * Returns the runtime ID of the entity the camera is attached to, or null if it is not attached.
     */
    public function getAttachedEntityId() : ?int{
        return $this->attachedEntityId;
    }

    /**
     * Returns the HUD preset last applied through the plugin, or null if the HUD was never changed.
     */
    public function getCurrentHud() : ?HudPreset{
        return $this->currentHud;
    }

    /**
     * Returns the control scheme packet last sent through this session, or null if none was sent.
     */
    public function getCurrentControlScheme() : ?ClientboundControlSchemeSetPacket{
        return $this->currentControlScheme;
    }

    /**
     * @internal Called by {@see HudPreset::send()}.
     */
    public function setCurrentHud(HudPreset $hud) : void{
        $this->currentHud = $hud;
    }

    /**
     * Sends the remembered camera, FOV, target, attachment, HUD, control scheme and fog state to the client
     * again.
     *
     * Use it when the client may have lost that state, for example after a respawn or dimension change. Eases are
     * dropped so the restored state is applied immediately instead of animating again.
     *
     * @return self
     */
    public function reapply() : self{
        if($this->currentSet !== null){
            $set = $this->currentSet;
            $this->sendPacket(CameraInstructionPacket::create(
                set: new CameraSetInstruction(
                    $set->getPreset(),
                    null,
                    $set->getCameraPosition(),
                    $set->getRotation(),
                    $set->getFacingPosition(),
                    $set->getViewOffset(),
                    $set->getEntityOffset(),
                    $set->getDefault(),
                    false
                ),
                clear: null,
                fade: null,
                target: null,
                removeTarget: null,
                fieldOfView: null,
                spline: null,
                attachToEntity: null,
                detachFromEntity: null
            ));
        }
        if($this->currentFov !== null){
            $fov = $this->currentFov;
            $this->sendPacket(CameraInstructionPacket::create(
                set: null,
                clear: null,
                fade: null,
                target: null,
                removeTarget: null,
                fieldOfView: new CameraFovInstruction($fov->getFieldOfView(), 0.0, $fov->getEaseType(), false),
                spline: null,
                attachToEntity: null,
                detachFromEntity: null
            ));
        }
        if($this->currentTarget !== null){
            $this->sendPacket(CameraInstructionPacket::create(
                set: null,
                clear: null,
                fade: null,
                target: $this->currentTarget,
                removeTarget: null,
                fieldOfView: null,
                spline: null,
                attachToEntity: null,
                detachFromEntity: null
            ));
        }
        if($this->attachedEntityId !== null){
            $this->attachToEntity($this->attachedEntityId);
        }
        if($this->currentHud !== null){
            $this->currentHud->send($this);
        }
        if($this->currentControlScheme !== null){
            $this->sendPacket($this->currentControlScheme);
        }
        if($this->fogStack !== []){
            $this->sendPacket(PlayerFogPacket::create(array_map(
                static fn(array $entry) : string => $entry['fogId'],
                $this->fogStack
            )));
        }
        return $this;
    }

    /**
     * Stops all active timeline tasks associated with this session.
     */
    public function stop() : self{
        foreach($this->activeTasks as $task){
            if(!$task->isCancelled()){
                $task->cancel();
            }
        }
        $this->activeTasks = [];

        $this->waitingSignal = null;
        $this->pausedTimelineQueue = [];
        $this->pausedTimeline = null;

        return $this;
    }

    /**
     * Whether a timeline is currently running for this session, i.e. it still has scheduled steps or is paused on
     * {@see CameraTimeline::waitUntil()}.
     */
    public function isTimelinePlaying() : bool{
        if($this->waitingSignal !== null){
            return true;
        }
        foreach($this->activeTasks as $task){
            if(!$task->isCancelled()){
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the name of the signal the current timeline is waiting for, or null if it is not waiting.
     */
    public function getWaitingSignal() : ?string{
        return $this->waitingSignal;
    }

    /**
     * Registers a timeline task to be managed by this session (cancelled when {@see self::stop()} is called).
     *
     * @param TaskHandler $task The scheduled task handle returned by the scheduler.
     *
     * @return self
     */
    public function addTimelineTask(TaskHandler $task) : self{
        $this->activeTasks[] = $task;
        return $this;
    }

    /**
     * @param string                                    $signalName
     * @param array<int, array{float, \Closure|string}> $remainingQueue
     * @param CameraTimeline                            $timeline
     *
     * @internal Called by {@see CameraTimeline} when a signal wait is encountered.
     *
     */
    public function setWaitingSignal(string $signalName, array $remainingQueue, CameraTimeline $timeline) : void{
        $this->waitingSignal = $signalName;
        $this->pausedTimelineQueue = $remainingQueue;
        $this->pausedTimeline = $timeline;
    }

    /**
     * Emits a signal to this session. If the session is currently waiting for
     * the given signal, the paused timeline will resume from where it stopped.
     *
     * @param string $signalName
     *
     * @return self
     */
    public function emitSignal(string $signalName) : self{
        if($this->waitingSignal !== $signalName || $this->pausedTimeline === null){
            return $this;
        }

        $this->waitingSignal = null;

        $queueToResume = $this->pausedTimelineQueue;
        $this->pausedTimelineQueue = [];

        $timeline = $this->pausedTimeline;
        $this->pausedTimeline = null;

        $timeline->playFromQueue($this, $queueToResume);
        return $this;
    }
}
