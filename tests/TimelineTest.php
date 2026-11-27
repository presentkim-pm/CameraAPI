<?php

declare(strict_types=1);

namespace kim\present\cameraapi\tests;

use kim\present\cameraapi\camera\preset\CameraPresetRegistry;
use kim\present\cameraapi\Main;
use kim\present\cameraapi\timeline\CameraTimelineParser;
use pocketmine\network\mcpe\protocol\CameraAimAssistPacket;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\ClientboundControlSchemeSetPacket;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;
use pocketmine\network\mcpe\protocol\SetHudPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskScheduler;

final class TimelineTest extends PacketTestCase{

    protected function setUp() : void{
        parent::setUp();

        // Timelines look up the plugin's scheduler, even when every step is immediate
        $main = (new \ReflectionClass(Main::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(PluginBase::class, 'scheduler'))->setValue($main, new TaskScheduler("test"));
        Main::setInstance($main);
    }

    public function testJsonTimelinePlaysEncodablePackets() : void{
        $timeline = CameraTimelineParser::fromJson(json_encode([
            'steps' => [
                ['type' => 'set', 'preset' => CameraPresetRegistry::PRESET_FREE, 'position' => [1, 2, 3], 'rotation' => [0, 90],
                    'ease' => ['type' => 'in_out_sine', 'duration' => 1.0]],
                ['type' => 'fade', 'in' => 0.5, 'stay' => 0.5, 'out' => 0.5, 'color' => [255, 0, 0]],
                ['type' => 'fov', 'set' => 80.0, 'ease' => ['type' => 3, 'duration' => 2.0]],
                ['type' => 'fov', 'clear' => true],
                ['type' => 'fog', 'push' => [['fogId' => 'minecraft:fog_hell']]],
                ['type' => 'controlScheme', 'scheme' => 'player_relative'],
                ['type' => 'target', 'entityId' => 42, 'offset' => [0, 1, 0]],
                ['type' => 'attachToEntity', 'entityId' => 42],
                ['type' => 'detachFromEntity'],
                ['type' => 'shake', 'intensity' => 1.0, 'duration' => 2.0, 'shakeType' => 'rotational'],
                ['type' => 'stopShake', 'shakeType' => 'rotational'],
                ['type' => 'hud', 'preset' => 'clear'],
                ['type' => 'aimAssist', 'preset' => 'minecraft:aim_assist_default', 'targetMode' => 'distance'],
                ['type' => 'clear'],
            ],
        ], JSON_THROW_ON_ERROR));

        $timeline->play($this->session);

        self::assertNotEmpty($this->sent);
        $classes = [];
        foreach($this->sent as $packet){
            $this->assertRoundTrip($packet);
            $classes[$packet::class] = true;
        }
        foreach([CameraInstructionPacket::class, PlayerFogPacket::class, ClientboundControlSchemeSetPacket::class,
                    CameraShakePacket::class, SetHudPacket::class, CameraAimAssistPacket::class] as $expected){
            self::assertArrayHasKey($expected, $classes, "$expected was not sent");
        }

        $ease = $this->sent[0];
        self::assertInstanceOf(CameraInstructionPacket::class, $ease);
        self::assertSame(CameraSetInstructionEaseType::IN_OUT_SINE, $ease->getSet()?->getEase()?->getType());
    }

    public function testEaseTypeAcceptsNamesAndOrdinals() : void{
        foreach(['linear', 'LINEAR', 'in_out_bounce', 0, '2', CameraSetInstructionEaseType::SPRING] as $type){
            $timeline = CameraTimelineParser::fromArray(['steps' => [
                ['type' => 'fov', 'set' => 70.0, 'ease' => ['type' => $type, 'duration' => 1.0]],
            ]]);
            $timeline->play($this->session);
        }
        self::assertCount(6, $this->sent);

        $ordinal = $this->sent[4];
        self::assertInstanceOf(CameraInstructionPacket::class, $ordinal);
        self::assertSame(CameraSetInstructionEaseType::IN_QUAD, $ordinal->getFieldOfView()?->getEaseType());
    }

    public function testInvalidEaseTypeIsRejected() : void{
        $this->expectException(\InvalidArgumentException::class);
        CameraTimelineParser::fromArray(['steps' => [
            ['type' => 'fov', 'set' => 70.0, 'ease' => ['type' => 'not_an_ease', 'duration' => 1.0]],
        ]]);
    }

    public function testInvalidEaseOrdinalIsRejected() : void{
        $this->expectException(\InvalidArgumentException::class);
        CameraTimelineParser::fromArray(['steps' => [
            ['type' => 'fov', 'set' => 70.0, 'ease' => ['type' => 999, 'duration' => 1.0]],
        ]]);
    }
}
