<?php

declare(strict_types=1);

namespace kim\present\cameraapi\tests;

use kim\present\cameraapi\Main;
use PHPUnit\Framework\TestCase;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\types\Experiments;

final class MainTest extends TestCase{

    public function testResourcePackStackGetsCreatorCamerasExperiment() : void{
        $main = (new \ReflectionClass(Main::class))->newInstanceWithoutConstructor();
        $packet = ResourcePackStackPacket::create([], false, "1.26.60", new Experiments([], false), false);

        $main->onDataPacketSend(new DataPacketSendEvent([], [$packet]));

        self::assertTrue($packet->experiments->getExperiments()["experimental_creator_cameras"] ?? false);
        $out = new \pmmp\encoding\ByteBufferWriter();
        $packet->encode($out);
        self::assertNotSame("", $out->getData());
    }
}
