<?php

declare(strict_types=1);

namespace kim\present\cameraapi\tests;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\Player;

/**
 * A Player that was never constructed (no server, world or network needed), which only knows its network session.
 */
final class StubPlayer extends Player{

    private NetworkSession $stubSession;

    public static function create(NetworkSession $networkSession) : self{
        $player = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $player->stubSession = $networkSession;
        return $player;
    }

    public function isConnected() : bool{
        return true;
    }

    public function getNetworkSession() : NetworkSession{
        return $this->stubSession;
    }

    public function __destruct(){
        //Nothing was initialized, so the regular destructor (which closes the entity) must not run
    }
}
