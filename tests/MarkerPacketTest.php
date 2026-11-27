<?php

declare(strict_types=1);

namespace kim\present\cameraapi\tests;

use kim\present\cameraapi\marker\CameraMarkerEntity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use PHPUnit\Framework\TestCase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

/**
 * The camera marker is shown to clients as a fake player. This runs the real entity code against stubbed
 * world / network objects and checks that the spawn packets it builds are valid for the current protocol.
 */
final class MarkerPacketTest extends TestCase{

    public function testSpawnPacketsAreValid() : void{
        $serverClass = new \ReflectionClass(Server::class);
        $server = $serverClass->newInstanceWithoutConstructor();

        $world = $this->createStub(World::class);
        $world->method('getServer')->willReturn($server);
        $world->method('isLoaded')->willReturn(true);

        CameraMarkerEntity::setSkin(new Skin("cameraapi:test", str_repeat("\x00", 64 * 64 * 4)));
        $marker = new CameraMarkerEntity(new Location(1.5, 64.0, -3.5, $world, 90.0, 10.0));
        $marker->setNameTag("Shot 1");

        /** @var ClientboundPacket[] $sent */
        $sent = [];
        $networkSession = $this->createStub(NetworkSession::class);
        $networkSession->method('sendDataPacket')->willReturnCallback(function(ClientboundPacket $pk) use (&$sent) : bool{
            $sent[] = $pk;
            return true;
        });
        $networkSession->method('getTypeConverter')->willReturn(new TypeConverter());
        $player = StubPlayer::create($networkSession);

        $method = new \ReflectionMethod($marker, 'sendSpawnPacket');
        $method->invoke($marker, $player);

        self::assertInstanceOf(PlayerListPacket::class, $sent[0]);
        self::assertSame(PlayerListPacket::TYPE_ADD, $sent[0]->getEntries()[0]->type);
        self::assertInstanceOf(AddPlayerPacket::class, $sent[1]);
        self::assertInstanceOf(PlayerListPacket::class, $sent[count($sent) - 1]);
        self::assertSame(PlayerListPacket::TYPE_REMOVE, $sent[count($sent) - 1]->getEntries()[0]->type);

        foreach($sent as $packet){
            if($packet instanceof SetActorDataPacket){ //sent through the (stub-less) broadcaster in a real server
                continue;
            }
            $this->roundTrip($packet);
        }
    }

    private function roundTrip(ClientboundPacket $packet) : void{
        $out = new \pmmp\encoding\ByteBufferWriter();
        $packet->encode($out);
        $class = $packet::class;
        $copy = new $class();
        $in = new \pmmp\encoding\ByteBufferReader($out->getData());
        $copy->decode($in);
        self::assertSame(0, $in->getUnreadLength());
        $out2 = new \pmmp\encoding\ByteBufferWriter();
        $copy->encode($out2);
        self::assertSame(bin2hex($out->getData()), bin2hex($out2->getData()));
    }
}
