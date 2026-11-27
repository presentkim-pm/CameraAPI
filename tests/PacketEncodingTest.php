<?php

declare(strict_types=1);

namespace kim\present\cameraapi\tests;

use kim\present\cameraapi\aimassist\AimAssistPresetRegistry;
use kim\present\cameraapi\aimassist\DefaultAimAssistPresetIds;
use kim\present\cameraapi\camera\preset\CameraPresetBuilder;
use kim\present\cameraapi\camera\preset\CameraPresetRegistry;
use kim\present\cameraapi\hud\HudPresetBuilder;
use kim\present\cameraapi\utils\ControlSchemePackets;
use pocketmine\block\utils\DyeColor;
use pocketmine\color\Color;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CameraAimAssistActorPriorityPacket;
use pocketmine\network\mcpe\protocol\CameraAimAssistPacket;
use pocketmine\network\mcpe\protocol\CameraAimAssistPresetsPacket;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\CameraPresetsPacket;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\ClientboundControlSchemeSetPacket;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;
use pocketmine\network\mcpe\protocol\SetHudPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistActionType;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategoryPriority;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistTargetMode;
use pocketmine\network\mcpe\protocol\types\camera\CameraPreset;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType;
use pocketmine\network\mcpe\protocol\types\ControlScheme;
use pocketmine\network\mcpe\protocol\types\hud\HudElement;
use pocketmine\network\mcpe\protocol\types\hud\HudVisibility;

/**
 * Builds every kind of packet through the plugin's builders and registries and runs it through the protocol's real
 * encoder and decoder, to prove that the plugin only produces valid Bedrock 1.26.60 packets.
 */
final class PacketEncodingTest extends PacketTestCase{

    public function testCameraSet() : void{
        $this->session->set()
                      ->preset(CameraPresetRegistry::PRESET_FREE)
                      ->ease(CameraSetInstructionEaseType::IN_OUT_BACK, 1.5)
                      ->position(new Vector3(1, 2, 3))
                      ->rotation(10.0, 20.0)
                      ->viewOffset(new Vector2(0.5, 0.25))
                      ->entityOffset(new Vector3(0, 1, 0))
                      ->setDefault()
                      ->send();

        $packet = $this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class));
        $set = $packet->getSet();
        self::assertNotNull($set);
        self::assertSame(CameraPresetRegistry::getIdByName(CameraPresetRegistry::PRESET_FREE), $set->getPreset());
        self::assertSame(CameraSetInstructionEaseType::IN_OUT_BACK, $set->getEase()?->getType());
        self::assertSame(1.5, $set->getEase()->getDuration());
        self::assertSame(2.0, $set->getCameraPosition()?->y);
        self::assertSame(20.0, $set->getRotation()?->getYaw());
        self::assertTrue($set->getDefault());
        self::assertSame($this->sentOne(CameraInstructionPacket::class)->getSet(), $this->session->getCurrentSet());
    }

    public function testCameraSetMinimal() : void{
        $this->session->set()->preset(CameraPresetRegistry::PRESET_FIRST_PERSON)->send();
        $set = $this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class))->getSet();
        self::assertNull($set?->getEase());
        self::assertNull($set->getCameraPosition());
    }

    public function testCameraFade() : void{
        $this->session->fade()->in(0.25)->stay(1.0)->out(0.75)->color(new Color(255, 0, 51))->send();

        $fade = $this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class))->getFade();
        self::assertSame(0.25, $fade?->getTime()?->getFadeInTime());
        self::assertSame(1.0, $fade->getTime()->getStayTime());
        self::assertSame(0.75, $fade->getTime()->getFadeOutTime());
        self::assertEqualsWithDelta(1.0, $fade->getColor()?->getRed(), 0.0001);
        self::assertEqualsWithDelta(0.2, $fade->getColor()->getBlue(), 0.0001);
    }

    public function testCameraFadeWithDyeColor() : void{
        $this->session->fade()->color(DyeColor::WHITE())->send();
        self::assertNotNull($this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class))->getFade());
    }

    public function testCameraFov() : void{
        $this->session->fov()->set(90.0)->ease(CameraSetInstructionEaseType::IN_CUBIC, 2.0)->send();

        $fov = $this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class))->getFieldOfView();
        self::assertSame(90.0, $fov?->getFieldOfView());
        self::assertSame(2.0, $fov->getEaseTime());
        self::assertSame(CameraSetInstructionEaseType::IN_CUBIC, $fov->getEaseType());
        self::assertFalse($fov->getClear());
        self::assertNotNull($this->session->getCurrentFov());
    }

    public function testCameraFovClear() : void{
        $this->session->fov()->clear()->send();
        self::assertTrue($this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class))->getFieldOfView()?->getClear());
        self::assertNull($this->session->getCurrentFov());
    }

    public function testCameraTarget() : void{
        $this->session->target()->entityId(1234)->offset(new Vector3(0, 1.5, 0))->send();

        $target = $this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class))->getTarget();
        self::assertSame(1234, $target?->getActorUniqueId());
        self::assertSame(1.5, $target->getTargetCenterOffset()?->y);
    }

    public function testCameraSpline() : void{
        $this->session->spline()
                      ->time(5.0)
                      ->ease(CameraSetInstructionEaseType::IN_OUT_SINE)
                      ->addPoint(new Vector3(0, 0, 0))
                      ->addPoint(new Vector3(5, 5, 5))
                      ->addPoint(new Vector3(10, 0, 10))
                      ->addRotation(new Vector3(10, 20, 0), 2.5, CameraSetInstructionEaseType::OUT_QUAD)
                      ->addProgress(0.5, 2.5, CameraSetInstructionEaseType::LINEAR)
                      ->send();

        $spline = $this->assertRoundTrip($this->sentOne(CameraInstructionPacket::class))->getSpline();
        self::assertSame(5.0, $spline?->getTotalTime());
        self::assertSame(CameraSetInstructionEaseType::IN_OUT_SINE, $spline->getEaseType());
        self::assertCount(3, $spline->getCurve());
        self::assertCount(1, $spline->getRotationOptions());
        self::assertSame(CameraSetInstructionEaseType::OUT_QUAD, $spline->getRotationOptions()[0]->getEaseType());
        self::assertCount(1, $spline->getProgressKeyFrames());
        self::assertSame("", $spline->getSplineIdentifier());
        self::assertFalse($spline->isLoadFromJson());
    }

    public function testClearAttachAndDetach() : void{
        $this->session->attachToEntity(77)->detachFromEntity()->clear();

        $attach = $this->assertRoundTrip($this->sent[0]);
        self::assertInstanceOf(CameraInstructionPacket::class, $attach);
        self::assertSame(77, $attach->getAttachToEntity());
        $detach = $this->assertRoundTrip($this->sent[1]);
        self::assertInstanceOf(CameraInstructionPacket::class, $detach);
        self::assertTrue($detach->getDetachFromEntity());
        $clear = $this->assertRoundTrip($this->sent[2]);
        self::assertInstanceOf(CameraInstructionPacket::class, $clear);
        self::assertTrue($clear->getClear());
    }

    public function testReapplyEncodesEveryRememberedInstruction() : void{
        $this->session->set()->preset(CameraPresetRegistry::PRESET_FREE)->position(new Vector3(1, 2, 3))->send();
        $this->session->fov()->set(60.0)->send();
        $this->session->target()->entityId(5)->send();
        $this->session->attachToEntity(9);
        $this->session->controlScheme()->cameraRelative();
        $this->sent = [];

        $this->session->reapply();
        self::assertNotEmpty($this->sent);
        foreach($this->sent as $packet){
            $this->assertRoundTrip($packet);
        }
    }

    public function testShake() : void{
        $this->session->shake(0.5, 2.0, CameraShakePacket::TYPE_ROTATIONAL)->stopShake(CameraShakePacket::TYPE_ROTATIONAL);

        $add = $this->assertRoundTrip($this->sent[0]);
        self::assertInstanceOf(CameraShakePacket::class, $add);
        self::assertSame(CameraShakePacket::ACTION_ADD, $add->getShakeAction());
        self::assertSame(CameraShakePacket::TYPE_ROTATIONAL, $add->getShakeType());
        $stop = $this->assertRoundTrip($this->sent[1]);
        self::assertInstanceOf(CameraShakePacket::class, $stop);
        self::assertSame(CameraShakePacket::ACTION_STOP, $stop->getShakeAction());
    }

    public function testFog() : void{
        $this->session->fog()->push("minecraft:fog_hell")->push("minecraft:fog_ocean", "mine")->send();
        self::assertCount(2, $this->assertRoundTrip($this->sentOne(PlayerFogPacket::class))->getFogLayers());
    }

    public function testHud() : void{
        HudPresetBuilder::create()->hideAll()->health()->crosshair()->build()->send($this->session);

        self::assertCount(2, $this->sent);
        $hide = $this->assertRoundTrip($this->sent[0]);
        self::assertInstanceOf(SetHudPacket::class, $hide);
        self::assertSame(HudVisibility::HIDE, $hide->getVisibility());
        self::assertCount(count(HudElement::cases()), $hide->getHudElements());
        $show = $this->assertRoundTrip($this->sent[1]);
        self::assertInstanceOf(SetHudPacket::class, $show);
        self::assertSame(HudVisibility::RESET, $show->getVisibility());
        self::assertEqualsCanonicalizing([HudElement::HEALTH, HudElement::CROSSHAIR], $show->getHudElements());
    }

    public function testControlSchemes() : void{
        foreach(ControlSchemePackets::getAll() as $name => $packet){
            $copy = $this->assertRoundTrip($packet);
            self::assertInstanceOf(ClientboundControlSchemeSetPacket::class, $copy);
            self::assertSame(ControlScheme::{$name}, $copy->getScheme());
        }

        $this->session->controlScheme()->byName("camera_relative_strafe");
        $sent = $this->sentOne(ClientboundControlSchemeSetPacket::class);
        self::assertSame(ControlScheme::CAMERA_RELATIVE_STRAFE, $sent->getScheme());
        self::assertSame($sent, $this->session->getCurrentControlScheme());
    }

    public function testCameraPresetsPacket() : void{
        CameraPresetRegistry::register(CameraPresetBuilder::create("cameraapi_test:everything")
            ->setParent(CameraPresetRegistry::PRESET_FREE)
            ->setPos(new Vector3(1.0, 2.0, 3.0))
            ->setPitch(4.0)
            ->setYaw(5.0)
            ->setSnapToTarget(true)
            ->setViewOffset(new Vector2(1, 2))
            ->setEntityOffset(new Vector3(1, 2, 3))
            ->setRadius(7.0)
            ->setAudioListenerType(CameraPreset::AUDIO_LISTENER_TYPE_PLAYER)
            ->setPlayerEffects(true)
            ->setControlScheme(ControlScheme::PLAYER_RELATIVE)
            ->setApplyInheritedStartingRotation(true)
            ->setStartingRotation(new Vector2(15.0, 30.0))
            ->build());

        $packet = $this->assertRoundTrip(CameraPresetRegistry::createCameraPresetsPacket());
        self::assertInstanceOf(CameraPresetsPacket::class, $packet);
        $byName = [];
        foreach($packet->getPresets() as $preset){
            $byName[$preset->getName()] = $preset;
        }
        foreach(["minecraft:first_person", "minecraft:fixed_boom", "minecraft:follow_orbit", "minecraft:free", "minecraft:third_person", "minecraft:third_person_front"] as $vanilla){
            self::assertArrayHasKey($vanilla, $byName);
        }

        $custom = $byName["cameraapi_test:everything"];
        self::assertSame(ControlScheme::PLAYER_RELATIVE, $custom->getControlScheme());
        self::assertTrue($custom->isApplyInheritedStartingRotation());
        self::assertSame(30.0, $custom->getStartingRotation()?->y);
        self::assertSame(7.0, $custom->getRadius());
    }

    public function testAimAssistPresetsPacket() : void{
        $packet = $this->assertRoundTrip(AimAssistPresetRegistry::createCameraAimAssistPresetsPacket());
        self::assertInstanceOf(CameraAimAssistPresetsPacket::class, $packet);
        self::assertNotEmpty($packet->getCategories());
        self::assertNotEmpty($packet->getPresets());
    }

    public function testAimAssist() : void{
        $this->session->aimAssist()
                      ->preset(DefaultAimAssistPresetIds::MINECRAFT_DEFAULT)
                      ->viewAngle(40.0, 20.0)
                      ->distance(8.0)
                      ->targetMode(CameraAimAssistTargetMode::DISTANCE)
                      ->debug()
                      ->send();
        $this->session->aimAssist()->clear()->send();

        $set = $this->assertRoundTrip($this->sent[0]);
        self::assertInstanceOf(CameraAimAssistPacket::class, $set);
        self::assertSame(CameraAimAssistActionType::SET, $set->getActionType());
        self::assertSame(CameraAimAssistTargetMode::DISTANCE, $set->getTargetMode());
        self::assertSame(8.0, $set->getDistance());
        $clear = $this->assertRoundTrip($this->sent[1]);
        self::assertInstanceOf(CameraAimAssistPacket::class, $clear);
        self::assertSame(CameraAimAssistActionType::CLEAR, $clear->getActionType());
    }

    public function testAimAssistActorPriority() : void{
        AimAssistPresetRegistry::registerCategoryDirect(
            identifier: "cameraapi_test:animals",
            entities: [new CameraAimAssistCategoryPriority("minecraft:cow", 60)]
        );
        $this->session->aimAssistActorPriority()
                      ->add(DefaultAimAssistPresetIds::MINECRAFT_DEFAULT, "cameraapi_test:animals", "minecraft:cow", 80)
                      ->send();

        $packet = $this->assertRoundTrip($this->sentOne(CameraAimAssistActorPriorityPacket::class));
        self::assertCount(1, $packet->getPriorityData());
        self::assertSame(80, $packet->getPriorityData()[0]->getPriority());
    }
}
