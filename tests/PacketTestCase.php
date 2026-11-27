<?php

declare(strict_types=1);

namespace kim\present\cameraapi\tests;

use kim\present\cameraapi\session\CameraSession;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * Base class that provides a camera session backed by a stub player, which records every packet sent to it, and
 * helpers to prove that a packet survives a real encode / decode round trip of the bedrock protocol.
 */
abstract class PacketTestCase extends TestCase{

    /** @var ClientboundPacket[] */
    protected array $sent = [];

    protected Player $player;
    protected CameraSession $session;

    protected function setUp() : void{
        parent::setUp();

        // The registries broadcast to Server::getInstance()->getOnlinePlayers(), which needs a Server instance
        $serverClass = new \ReflectionClass(Server::class);
        $property = $serverClass->getProperty('instance');
        if($property->getValue() === null){
            $property->setValue(null, $serverClass->newInstanceWithoutConstructor());
        }

        $this->sent = [];
        $networkSession = $this->createStub(NetworkSession::class);
        $networkSession->method('sendDataPacket')->willReturnCallback(function(ClientboundPacket $pk) : bool{
            $this->sent[] = $pk;
            return true;
        });

        $this->player = StubPlayer::create($networkSession);

        $this->session = new CameraSession($this->player);
    }

    /**
     * Encodes the packet, decodes it into a fresh instance and checks that nothing is left over and that
     * re-encoding the decoded packet gives the same bytes.
     *
     * @template T of DataPacket
     * @param T $packet
     *
     * @return T The decoded copy.
     */
    protected function assertRoundTrip(DataPacket $packet) : DataPacket{
        $out = new ByteBufferWriter();
        $packet->encode($out);
        $bytes = $out->getData();
        self::assertNotSame("", $bytes);

        $class = $packet::class;
        $copy = new $class();
        $in = new ByteBufferReader($bytes);
        $copy->decode($in);
        self::assertSame(0, $in->getUnreadLength(), "$class left unread bytes after decoding");

        $out2 = new ByteBufferWriter();
        $copy->encode($out2);
        self::assertSame(bin2hex($bytes), bin2hex($out2->getData()), "$class changed after a round trip");

        return $copy;
    }

    /**
     * @template T of ClientboundPacket
     * @param class-string<T> $class
     *
     * @return T The only packet of that class that was sent
     */
    protected function sentOne(string $class) : ClientboundPacket{
        $found = array_values(array_filter($this->sent, static fn(ClientboundPacket $pk) : bool => $pk instanceof $class));
        self::assertCount(1, $found, "Expected exactly one $class to be sent");
        return $found[0];
    }
}
