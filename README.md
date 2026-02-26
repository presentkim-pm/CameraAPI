<!-- PROJECT BADGES -->
<div align="center">

![Version][version-badge]
[![Stars][stars-badge]][stars-url]
[![License][license-badge]][license-url]

</div>


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <img src="https://raw.githubusercontent.com/presentkim-pm/CameraAPI/main/assets/icon.png" alt="Logo" width="80" height="80">
  <h3>CameraAPI</h3>
  <p align="center">
    A high-level camera control API for Minecraft Bedrock (PocketMine-MP 5.x).

[Contact to me][author-discord] · [Report a bug][issues-url] · [Request a feature][issues-url]

  </p>
</div>


<!-- ABOUT THE PROJECT -->

### Overview

- **What it does**
  - Wraps low-level packets like `CameraInstructionPacket` and `CameraPresetsPacket` behind a fluent PHP API.
  - Manages per-player `CameraSession` objects and exposes builder-style helpers for sending camera instructions.
- **Why it exists**
  - Bedrock Creator Cameras are powerful but difficult to use directly: packet structures are complex and
    preset/timeline management is verbose.
  - This plugin hides that complexity behind **simple method chaining** and a **timeline DSL**.
- **Key concepts**
  - `Camera::of(Player)` – main entry point to obtain a camera session for a player.
  - `CameraSession` – per-player object that manages camera state and scheduled tasks.
  - Builders – `CameraSetBuilder`, `CameraFadeBuilder`, `CameraTargetBuilder`, `CameraFovBuilder`,
    `CameraSplineBuilder` (experimental / discouraged).
  - `CameraTimeline` – utility to queue and play camera instructions over time (cutscenes).
  - `CameraPresetRegistry` / `CameraPresetBuilder` – registration and synchronization of camera presets with the client.
  - `CameraMarker` – in-world helper entity (fake player) to place and preview camera viewpoints; supports interact and attack callbacks.

---

### Installation & Requirements

- **Server**
  - PocketMine-MP `5.0.0` or higher.
- **Installation**
  - Clone this repository or place the `CameraAPI` plugin folder into your PMMP server's `plugins` directory.
  - Restart the server; the plugin will load automatically according to `plugin.yml`.
- **How it works at runtime**
  - The `Main` plugin class is loaded and:
    - On `PlayerJoinEvent`, `CameraSessionManager::createSession($player)` creates a session.
    - `CameraPresetRegistry::sendTo($player)` sends camera presets to the client.
  - When `StartGamePacket` or `ResourcePackStackPacket` is sent, the plugin automatically enables the
    `experimental_creator_cameras` experiment flag.

---

### Quick Start

```php
use kim\present\cameraapi\Camera;
use pocketmine\player\Player;

function example(Player $player) : void{
    // Get camera session for this player
    $session = Camera::of($player);

    // Fade to black for 1s, stay 1s, fade out for 1s
    $session->fade()
        ->in(1.0)
        ->stay(1.0)
        ->out(1.0)
        ->send();
}
```

---

## API Documentation

### 1. Entry Point: `Camera`

```php
Camera::of(Player $player) : CameraSession
```

- **Description**: Returns the `CameraSession` for the given player, creating it if necessary.
- **Parameters**
  - `Player $player` – target player whose camera will be controlled.
- **Returns**
  - `CameraSession` – session object managing camera state and commands for the player.
- **Example**

```php
$session = Camera::of($player);
$session->clear(); // Reset camera to default
```

- **Timeline helpers**

```php
Camera::timeline() : CameraTimeline
Camera::loadTimeline(string $json) : CameraTimeline
```

- `timeline()` creates an empty, code-driven timeline.
- `loadTimeline()` builds a timeline from a JSON description using `CameraTimelineParser`.

---

### 2. `CameraSession`

The session keeps camera context per player and provides builders and utility methods.

- **Method summary**
  - `getPlayer() : ?Player`
  - `set() : CameraSetBuilder`
  - `fade() : CameraFadeBuilder`
  - `target() : CameraTargetBuilder`
  - `fov() : CameraFovBuilder`
  - `hud(HudPreset|string $presetOrName) : self` – apply a HUD preset by instance or registry name (see §6).
  - `spline() : CameraSplineBuilder` (**deprecated**, do not use in production)
  - `shake(float $intensity = 0.5, float $duration = 1.0, int $type = CameraShakePacket::TYPE_POSITIONAL) : self`
  - `stopShake(int $type = CameraShakePacket::TYPE_POSITIONAL) : self`
  - `clear() : self`
  - `sendPacket(ClientboundPacket $pk) : self`
  - `stop() : self`
  - `addTimelineTask(TaskHandler $task) : self`
  - `emitSignal(string $signalName) : self` – resume a timeline waiting for this signal (see §3.1).

#### 2.1 Position / Rotation: `set() : CameraSetBuilder`

```php
$session->set()
    ->preset("minecraft:free")
    ->position($pos)
    ->rotation(30, 90)
    ->ease(CameraSetInstructionEaseType::LINEAR, 2.0)
    ->send();
```

- Or, if you prefer to orient the camera toward a specific point:

```php
use pocketmine\math\Vector3;

$target = new Vector3(0, 64, 0); // Look at this world-space position

$session->set()
    ->preset("minecraft:free")
    ->position($pos)
    ->rotationTo($target) // compute pitch/yaw so the camera looks at $target
    ->send();
```

- **Key methods**
  - `preset(string $preset) : self`
    - e.g. `"minecraft:first_person"`, `"minecraft:third_person"`, `"minecraft:free"`, or your custom presets.
  - `ease(int $type, float $duration) : self`
    - Use `CameraSetInstructionEaseType` constants (e.g. `EaseType::LINEAR`).
  - `position(Vector3 $position) : self`
  - `positionOffset(Vector3 $offset) : self` – world-space offset from the player's current position.
  - `positionLocal(Vector3 $offset) : self` – local-space offset relative to the player's view (X: right/left, Y: up/down, Z: forward/backward).
  - `rotation(float $pitch, float $yaw) : self`
  - `rotationTo(Vector3 $target) : self` – compute and set rotation so the camera at the current position looks at the given world-space target.
  - `facing(Vector3 $position) : self`
  - `facingOffset(Vector3 $offset) : self` – world-space offset for the target the camera should look at.
  - `facingLocal(Vector3 $offset) : self` – local-space offset for the facing target, using the same convention as `positionLocal()`.
  - `viewOffset(Vector2 $offset) : self`
  - `entityOffset(Vector3 $offset) : self`
  - `setDefault(bool $value = true) : self`
  - `send() : CameraSession`

**World vs local offsets**

- World-space helpers (`positionOffset`, `facingOffset`) simply add the given offset to the player's current world position.
- Local-space helpers (`positionLocal`, `facingLocal`) interpret the offset in the player's view space:
  - X: right (+) / left (-)
  - Y: up (+) / down (-)
  - Z: forward (+) / backward (-)

#### 2.2 Screen Fade: `fade() : CameraFadeBuilder`

```php
use pocketmine\color\Color;

$session->fade()
    ->in(0.5)      // Fade in over 0.5 seconds
    ->stay(1.0)    // Stay fully opaque for 1 second
    ->out(0.5)     // Fade out over 0.5 seconds
    ->color(Color::fromRGB(255, 0, 0)) // Red curtain color
    ->send();
```

- **Methods**
  - `in(float $seconds) : self`
  - `stay(float $seconds) : self`
  - `out(float $seconds) : self`
  - `color(Color $color) : self`
  - `send() : CameraSession`

#### 2.3 Target Tracking: `target() : CameraTargetBuilder`

```php
use pocketmine\math\Vector3;

$session->target()
    ->entity($targetEntity)
    ->offset(new Vector3(0, 1.6, 0)) // Look at the entity's head
    ->send();
```

- **Methods**
  - `offset(Vector3 $offset) : self`
  - `entity(?Entity $entity) : self` – set or clear the tracked entity (null to clear).
  - `entityId(?int $id) : self` – set or clear the tracked entity ID (null to clear).
  - `send() : CameraSession`

#### 2.4 FOV Control: `fov() : CameraFovBuilder`

```php
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType as EaseType;

$session->fov()
    ->set(90.0)
    ->ease(EaseType::IN_CUBIC, 1.0)
    ->send();
```

- **Methods**
  - `set(float $fov) : self` — default is 70.
  - `ease(int $type, float $duration) : self`
  - `send() : CameraSession`

#### 2.5 Shake / Reset

```php
// Apply camera shake
$session->shake(0.8, 2.0);

// Stop shaking
$session->stopShake();

// Clear all camera instructions and reset
$session->clear();
```

---

### 3. `CameraTimeline` (Cutscenes / Sequences)

`CameraTimeline` lets you queue multiple camera instructions in **time order** and play them as a cutscene.

```php
use kim\present\cameraapi\timeline\CameraTimeline;

$timeline = new CameraTimeline();

$timeline
    ->fade(fn($b) => $b->in(0.5)->stay(0.5)->out(0.5))
    ->wait(0.5)
    ->set(fn($b) => $b->preset("minecraft:free")->position($pos))
    ->wait(3.0)
    ->shake(0.6, 1.5)
    ->wait(1.0)
    ->clear();

$timeline->play($player);
```

- **Method overview**
  - `wait(float $seconds) : self`
  - `waitUntil(string $signalName) : self` – pause the timeline until the given signal is emitted for that player's `CameraSession` (see below).
  - `set(\Closure(CameraSetBuilder): void $setup) : self`
  - `fade(\Closure(CameraFadeBuilder): void $setup) : self`
  - `target(\Closure(CameraTargetBuilder): void $setup) : self`
  - `fov(\Closure(CameraFovBuilder): void $setup) : self`
  - `spline(\Closure(CameraSplineBuilder): void $setup) : self`
  - `shake(float $intensity = 0.5, float $duration = 1.0, int $type = CameraShakePacket::TYPE_POSITIONAL) : self`
  - `stopShake(int $type = CameraShakePacket::TYPE_POSITIONAL) : self`
  - `clear() : self`
  - `setLoop(bool $loop = true) : self` – when enabled, the full sequence automatically restarts after it finishes, until the underlying `CameraSession` is stopped.
  - `play(Player $player) : void`

To loop a timeline indefinitely until you clear/stop the camera session:

```php
$timeline
    ->set(/* ... */)
    ->wait(2.0)
    ->clear()
    ->setLoop()   // enable looping
    ->play($player);
```

**Note**: `spline()` uses `CameraSplineBuilder` under the hood, which is currently marked **deprecated** due to client
crash / disconnect issues.

#### 3.1 Signal-based waiting (no per-tick polling)

`waitUntil()` lets you pause a timeline until an external event (boss spawn, door open, etc.) explicitly resumes it.  
This avoids per-tick polling and integrates cleanly with PocketMine-MP's single-threaded scheduler.

**Concept**

- In the middle of a timeline, insert `->waitUntil("some_signal")`.
- The timeline stops scheduling further actions when it reaches that point.
- Later, another plugin or event handler calls `$session->emitSignal("some_signal")` for each player that should resume.

**Example – boss spawn cutscene**

```php
use kim\present\cameraapi\Camera;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

function playBossIntro(Player $player, Vector3 $doorPos, Vector3 $bossPos) : void{
    $timeline = Camera::timeline()
        ->set(fn($b) => $b->preset("minecraft:free")->position($doorPos))
        ->wait(2.0)
        ->waitUntil("boss_spawned") // pause here until the boss actually spawns
        ->set(fn($b) => $b->position($bossPos))
        ->shake(0.8, 2.0)
        ->wait(2.0)
        ->clear();

    $timeline->play($player);
}
```

In your boss plugin or event listener:

```php
use kim\present\cameraapi\Camera;

public function onBossSpawn(BossSpawnEvent $event) : void{
    foreach($event->getPlayersInArena() as $player){
        $session = Camera::of($player);
        $session->emitSignal("boss_spawned");
    }
}
```

**Notes**  
- `waitUntil()` executes the timeline in **chunks (segments between signals)** and stops scheduling the next chunk until the specified signal is emitted.  
- This allows you to implement complex cutscene state machines **without any per-tick polling**.

#### 3.2 Data-driven timelines (JSON)

You can also describe timelines in JSON (or arrays) and load them at runtime.  
This is useful if non-programmers (builders / designers) need to tweak cutscenes without touching PHP code.

**Supported JSON step types**

- `wait` – `{ "type": "wait", "seconds": 2.0 }`
- `waitUntil` – `{ "type": "waitUntil", "signal": "boss_spawned" }`
- `shake` – `{ "type": "shake", "intensity": 0.8, "duration": 1.5 }`
- `stopShake` – `{ "type": "stopShake" }`
- `clear` – `{ "type": "clear" }`
- `set` – camera position / preset:

  ```json
  {
    "type": "set",
    "preset": "minecraft:free",
    "position": [100, 60, 100],
    "rotation": [30, 90],
    "facing": [100, 60, 120],
    "ease": { "type": 0, "duration": 1.0 }
  }
  ```

- `fade` – screen fade:

  ```json
  {
    "type": "fade",
    "in": 0.5,
    "stay": 1.0,
    "out": 0.5
  }
  ```

- `fov` – field of view:

  ```json
  {
    "type": "fov",
    "set": 90.0,
    "ease": { "type": 0, "duration": 1.0 }
  }
  ```

**Full example – `boss_intro.json`**

```json
{
  "loop": false,
  "steps": [
    {
      "type": "fade",
      "in": 0.5,
      "stay": 1.0,
      "out": 0.5
    },
    {
      "type": "set",
      "preset": "minecraft:free",
      "position": [100, 60, 100],
      "rotation": [30, 90]
    },
    {
      "type": "fov",
      "set": 90.0,
      "ease": { "type": 0, "duration": 1.0 }
    },
    {
      "type": "wait",
      "seconds": 2.0
    },
    {
      "type": "shake",
      "intensity": 0.8,
      "duration": 1.5
    },
    {
      "type": "wait",
      "seconds": 1.5
    },
    {
      "type": "clear"
    }
  ]
}
```

**Loading a JSON timeline**

```php
use kim\present\cameraapi\Camera;

$json = file_get_contents($this->getDataFolder() . "cutscenes/boss_intro.json");
$timeline = Camera::loadTimeline($json);
$timeline->play($player);
```

---

### 4. Presets: `CameraPresetRegistry` / `CameraPresetBuilder`

On the client, preset names like `"minecraft:free"` are resolved from a list sent by the server.  
This plugin registers **vanilla presets** and provides an API for registering your own.

- **Built-in constants**
  - `PRESET_FIRST_PERSON = "minecraft:first_person"`
  - `PRESET_FIXED_BOOM = "minecraft:fixed_boom"`
  - `PRESET_FOLLOW_ORBIT = "minecraft:follow_orbit"`
  - `PRESET_FREE = "minecraft:free"`
  - `PRESET_THIRD_PERSON = "minecraft:third_person"`
  - `PRESET_THIRD_PERSON_FRONT = "minecraft:third_person_front"`

#### 4.1 Registering a preset

```php
use kim\present\cameraapi\preset\CameraPresetBuilder;
use kim\present\cameraapi\preset\CameraPresetRegistry;
use pocketmine\math\Vector3;

CameraPresetRegistry::register(
    CameraPresetBuilder::create("myplugin:topdown")
        ->setPos(new Vector3(0, 50, 0))
        ->setPitch(-90.0)
        ->build()
);
```

- You can call this after the server starts (e.g. in another plugin's `onEnable()`),  
  and then re-sync presets to a specific player via `CameraPresetRegistry::sendTo($player)`.

#### 4.2 Looking up / using presets

```php
use kim\present\cameraapi\preset\CameraPresetRegistry;

$id = CameraPresetRegistry::getIdByName("myplugin:topdown"); // int|null
```

`CameraSetBuilder::preset()` internally uses `getIdByName()`, so in most cases you only need to pass the preset name
string.

---

### 5. Camera markers (in-world placement)

Camera markers are small in-world entities you can spawn to define camera positions and orientations. They appear as
fake players (with a plugin-supplied skin), support yaw/pitch from the spawn location, and can have an interact button
and callbacks for left-click (attack) and right-click (interact). Use them for map creation or cutscene keyframes.

- **Spawning**: `Camera::spawnMarker(Location $location, ?string $label = null) : CameraMarker`
  - `$location` – world position and rotation (yaw/pitch) for the marker; e.g. use the player's location (optionally
    offset to eye height) so the marker faces the same direction.
- **Methods on `CameraMarker`**
  - `setPosition(Vector3 $pos)`, `lookAt(Vector3 $target)`, `setNameTag(string $name)`, `setInteractButton(string $buttonText)`
  - `setOnAttack(?\Closure $onAttack)` – callback when a player left-clicks the marker (e.g. remove it).
  - `setOnClick(?\Closure $onClick)` – callback when a player right-clicks the marker (e.g. apply marker to camera and play a timeline).
  - `applyToSession(CameraSession $session, ?int $easeType = null, ?float $easeDuration = null)` – sets the camera to the marker's position and rotation (vanilla FREE preset). Optionally pass an ease type (e.g. `CameraSetInstructionEaseType::LINEAR`) and duration in seconds for a smooth transition.
  - `remove()` – despawns the marker.

**Example: create a marker, remove on attack, preview on interact**

```php
use kim\present\cameraapi\Camera;
use kim\present\cameraapi\marker\CameraMarker;
use kim\present\cameraapi\session\CameraSession;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

// Create a marker at the player's eye position, facing the same direction
$markerLocation = $sender->getLocation();
$markerLocation->y += $sender->getEyeHeight();
$this->markers[$markerName] = Camera::spawnMarker($markerLocation, $markerName)
    ->setOnAttack(function($_, Player $player) use ($markerName) : void{
        $this->markers[$markerName]->remove();
        unset($this->markers[$markerName]);
        $player->sendMessage(TextFormat::GREEN . "Removed '$markerName' camera marker.");
    })
    ->setInteractButton("Test")
    ->setOnClick(fn(CameraMarker $marker, Player $player) => Camera::timeline()
        ->add(fn(CameraSession $session) => $marker->applyToSession($session))
        ->wait(3)
        ->clear()
        ->play($player)
    );
$sender->sendMessage(TextFormat::GREEN . "Created '$markerName' camera marker.");
```

- Right-clicking the marker applies its pose to the player's camera, holds for 3 seconds, then clears. Left-clicking
  removes the marker.

**Using ease for smooth transitions**

You can pass an ease type and duration so the camera moves smoothly to the marker's pose instead of snapping:

```php
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType as EaseType;

// Instant (default)
$marker->applyToSession($session);

// 1.5 second linear transition to the marker's position and rotation
$marker->applyToSession($session, EaseType::LINEAR, 1.5);

// 2 second cubic ease-in transition
$marker->applyToSession($session, EaseType::IN_CUBIC, 2.0);
```

---

### 6. HUD presets (utility)

CameraAPI provides **HUD presets**: immutable layouts that describe which `HudElement` entries should be visible.  
They can be applied directly to a `Player` or `CameraSession` via `send()`, or via **`CameraSession::hud()`** (see below). Optional presets can be stored in `HudPresetRegistry`.

- **Classes**
  - `kim\present\cameraapi\hud\HudPreset` – immutable preset value object
  - `kim\present\cameraapi\hud\HudPresetBuilder` – fluent builder for presets
  - `kim\present\cameraapi\hud\HudPresetRegistry` – string-keyed registry of presets

**HudPreset**

- `readonly` class with one `bool` property per HUD element:
  - `paperDoll`, `armor`, `tooltips`, `touchControls`, `crosshair`, `hotbar`,
    `health`, `xp`, `food`, `airBubbles`, `horseHealth`, `statusEffects`, `itemText`
- Constructor defaults every flag to `true` so you can use **named parameters** to turn off only what you need:

```php
use kim\present\cameraapi\hud\HudPreset;

// Hide everything (e.g. for a clean cinematic shot)
$clear = new HudPreset(
    paperDoll: false,
    armor: false,
    tooltips: false,
    touchControls: false,
    crosshair: false,
    hotbar: false,
    health: false,
    xp: false,
    food: false,
    airBubbles: false,
    horseHealth: false,
    statusEffects: false,
    itemText: false,
);

$clear->send($player);
// or
$clear->send(Camera::of($player));
```

- **`send(CameraSession|Player $target) : void`**  
  Applies this preset to the target: hides all known HUD elements, then re-enables those flagged `true` in this preset. No-op if the player is offline or disconnected.

- **`getVisibleElements() : list<HudElement>`**  
  Returns the list of `HudElement` enum cases that are enabled in this preset.

- **`fromVisibleElements(list<HudElement> $visibleElements) : self`**  
  Static factory: builds a preset where only the given HUD elements are visible; all others are hidden.

**HudPresetBuilder**

Fluent builder if you prefer a chainable API over named parameters:

- **`create() : self`** – new builder with all elements enabled.
- **Per-element setters** – `paperDoll(bool $value = true)`, `armor(bool $value = true)`, …, `itemText(bool $value = true)`.
- **`hideAll() : self`** – sets every element to false.
- **`build() : HudPreset`** – returns an immutable preset from the current state.

```php
use kim\present\cameraapi\hud\HudPresetBuilder;

$minimal = HudPresetBuilder::create()
    ->hideAll()
    ->hotbar(true)
    ->health(true)
    ->build();

$minimal->send($player);
```

**HudPresetRegistry**

- **Built-in presets**
  - `HudPresetRegistry::PRESET_DEFAULT` – all HUD elements visible (`new HudPreset()`).
  - `HudPresetRegistry::PRESET_CLEAR` – all HUD elements hidden (`HudPreset::fromVisibleElements([])`).

- **API**
  - `register(string $name, HudPreset $preset) : void` – register or overwrite a preset by name (case-insensitive).
  - `get(string $name) : ?HudPreset` – get a preset by name.
  - `isRegistered(string $name) : bool` – whether a preset with that name exists.
  - `getAll() : array<string, HudPreset>` – all registered presets (built-in + custom), keyed by lowercased name.

**Applying HUD from a session**

You can apply a preset from a `CameraSession` with **`hud(HudPreset|string)`**: pass either a `HudPreset` instance or a name registered in `HudPresetRegistry`. Returns the session for chaining.

```php
use kim\present\cameraapi\Camera;
use kim\present\cameraapi\hud\HudPresetRegistry;

$session = Camera::of($player);

// By registry name (e.g. hide all HUD for a cutscene)
$session->hud(HudPresetRegistry::PRESET_CLEAR);

// By preset instance
$session->hud(new HudPreset(crosshair: true, hotbar: true));
```

**Example – registry and custom preset**

```php
use kim\present\cameraapi\hud\HudPresetRegistry;

// Apply built-in “all hidden” preset
$preset = HudPresetRegistry::get(HudPresetRegistry::PRESET_CLEAR);
if($preset !== null){
    $preset->send($player);
}
```

```php
use kim\present\cameraapi\hud\HudPresetBuilder;
use kim\present\cameraapi\hud\HudPresetRegistry;

// Register a custom preset and use it later
$minimal = HudPresetBuilder::create()->hideAll()->hotbar(true)->build();
HudPresetRegistry::register("minimal_hotbar", $minimal);

$preset = HudPresetRegistry::get("minimal_hotbar");
if($preset !== null){
    $preset->send($player);
}
```

---

## Implementation & Architecture Overview

- **Plugin entry (`Main`)**
  - Extends PMMP `PluginBase` and implements `Listener`.
  - Calls `CameraSessionManager::init()` and registers event listeners in `onEnable()`.
  - On `PlayerJoinEvent`: creates session + syncs presets; on `PlayerQuitEvent`: removes session.
  - Hooks `DataPacketSendEvent` and sets the `experimental_creator_cameras` flag on `StartGamePacket` /
    `ResourcePackStackPacket` `Experiments`.

- **Session management (`CameraSessionManager`)**
  - Uses an internal `\WeakMap<Player, CameraSession>` so sessions are automatically cleaned up with the player objects.
  - Provides `createSession()`, `getSession()`, `removeSession()`.

- **Timeline (`CameraTimeline`)**
  - Uses the server scheduler and `ClosureTask` to convert seconds to ticks (`20 ticks/sec`) and schedule actions.
  - Stores `TaskHandler` instances in the `CameraSession` and cancels them on `session->stop()` to avoid **memory leaks
    and orphaned tasks**.

- **Dependencies**
  - PocketMine-MP 5.x network packets and camera types.
  - PHP 8 `WeakReference` / `WeakMap`.

---

## Examples

### Example 1. Simple intro fade + camera move

```php
use kim\present\cameraapi\Camera;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\math\Vector3;

class IntroListener implements Listener{
    public function onJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $session = Camera::of($player);

        $spawn = $player->getWorld()->getSpawnLocation()->add(0, 10, 0);

        $session->fade()
            ->in(0.5)->stay(0.5)->out(0.5)
            ->send();

        $session->set()
            ->preset("minecraft:free")
            ->position($spawn)
            ->facing($player->getPosition())
            ->send();
    }
}
```

### Example 2. Boss cutscene timeline

```php
use kim\present\cameraapi\timeline\CameraTimeline;
use kim\present\cameraapi\Camera;
use pocketmine\math\Vector3;

function playBossCutscene(Player $player, Vector3 $bossPos) : void{
    $timeline = new CameraTimeline();

    $timeline
        ->fade(fn($b) => $b->in(0.5)->stay(0.5))
        ->wait(0.5)
        ->set(fn($b) => $b
            ->preset("minecraft:free")
            ->position($bossPos->add(0, 15, -10))
            ->facing($bossPos)
        )
        ->wait(3.0)
        ->shake(0.7, 2.0)
        ->wait(1.0)
        ->clear();

    $timeline->play($player);
}
```

### Example 3. Top‑down minigame using presets

```php
use kim\present\cameraapi\preset\CameraPresetBuilder;
use kim\present\cameraapi\preset\CameraPresetRegistry;
use kim\present\cameraapi\Camera;
use pocketmine\math\Vector3;

function registerTopdownPreset() : void{
    CameraPresetRegistry::register(
        CameraPresetBuilder::create("mygame:topdown")
            ->setPos(new Vector3(0, 30, 0))
            ->setPitch(-90.0)
            ->build()
    );
}

function applyTopdownCamera(Player $player) : void{
    CameraPresetRegistry::sendTo($player);

    Camera::of($player)->set()
        ->preset("mygame:topdown")
        ->send();
}
```

---

## Best Practices & Pitfalls

- **Reuse sessions**
  - `Camera::of($player)` internally caches sessions, so calling it repeatedly is cheap and recommended.
- **Timeline overlapping**
  - `CameraTimeline::play()` calls `CameraSession::stop()` first, cancelling any existing timeline tasks for that
    player.
  - If you want multiple overlapping timelines, you must manage scheduling and cancellation yourself.
- **Avoid `spline()` for now**
  - `CameraSession::spline()` and `CameraTimeline::spline()` use `CameraSplineBuilder`, which is currently known to
    cause client crashes / disconnects and is marked deprecated.
- **Experimental flag conflicts**
  - This plugin automatically enables `experimental_creator_cameras`, but if other plugins also modify `StartGamePacket`
    or `ResourcePackStackPacket`, be careful about interaction and ordering.
- **Exceptions & edge cases**
  - Most APIs are designed to safely no-op if the player is offline or the packet cannot be sent.
  - If you reference a non-registered preset name, `CameraPresetRegistry::get()` may throw `\InvalidArgumentException`.
    - Prefer registering all presets during your plugin's initialization and avoid typos in preset names.

-----

[author-discord]: https://discordapp.com/users/345772340279508993

[version-badge]: https://img.shields.io/github/v/release/presentkim-pm/CameraAPI?display_name=tag&style=for-the-badge&label=VERSION

[release-badge]: https://img.shields.io/github/downloads/presentkim-pm/CameraAPI/total?style=for-the-badge&label=GITHUB%20

[stars-badge]: https://img.shields.io/github/stars/presentkim-pm/CameraAPI.svg?style=for-the-badge

[license-badge]: https://img.shields.io/github/license/presentkim-pm/CameraAPI.svg?style=for-the-badge

[stars-url]: https://github.com/presentkim-pm/CameraAPI/stargazers

[releases-url]: https://github.com/presentkim-pm/CameraAPI/releases

[issues-url]: https://github.com/presentkim-pm/CameraAPI/issues

[license-url]: https://github.com/presentkim-pm/CameraAPI/blob/main/LICENSE

[project-icon]: https://raw.githubusercontent.com/presentkim-pm/CameraAPI/main/assets/icon.png
