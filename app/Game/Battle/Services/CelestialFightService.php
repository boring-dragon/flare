<?php

namespace App\Game\Battle\Services;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\CelestialTimeOut;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Core\Events\UpdateCharacterCelestialTimeOut;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\MercenaryBonus;
use App\Game\Mercenaries\Values\MercenaryValue;
use Facades\App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Game\Battle\Events\UpdateCelestialFight;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class CelestialFightService {

    use ResponseBuilder, MercenaryBonus;

    private BattleEventHandler $battleEventHandler;

    private CharacterCacheData $characterCacheData;

    private ?MonsterPlayerFight $monsterPlayerFight;

    public function __construct(BattleEventHandler $battleEventHandler, CharacterCacheData $characterCacheData, MonsterPlayerFight $monsterPlayerFight) {
        $this->battleEventHandler = $battleEventHandler;
        $this->characterCacheData = $characterCacheData;
        $this->monsterPlayerFight = $monsterPlayerFight;
    }

    public function joinFight(Character $character, CelestialFight $celestialFight): CharacterInCelestialFight {
        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();

        $health = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if (is_null($characterInCelestialFight)) {
            $characterInCelestialFight = CharacterInCelestialFight::create([
                'celestial_fight_id'      => $celestialFight->id,
                'character_id'            => $character->id,
                'character_max_health'    => $health,
                'character_current_health'=> $health,
            ]);
        } else {
            if (now()->diffInMinutes($characterInCelestialFight->updated_at) > 5) {
                $characterInCelestialFight = $this->updateCharacterInFight($character, $characterInCelestialFight);
            }

            if ($health !== $characterInCelestialFight->character_current_health) {
                $characterInCelestialFight = $this->updateCharacterInFight($character, $characterInCelestialFight);
            }
        }

        return $characterInCelestialFight;
    }

    public function fight(Character $character, CelestialFight $celestialFight, CharacterInCelestialFight $characterInCelestialFight, string $attackType): array {

        $result = $this->monsterPlayerFight->setUpFight($character, [
            'attack_type' => $attackType,
            'selected_monster_id' => $celestialFight->monster_id
        ])->fightMonster(true);

        if ($result) {

            $messages = $this->monsterPlayerFight->getBattleMessages();

            $this->monsterPlayerFight = null;

            $characterHealth = $characterInCelestialFight->character_max_health;

            $this->handleMonsterDeath($character, $celestialFight);

            return $this->successResult([
                'logs' => $messages,
                'health' => [
                    'character_health' => $characterHealth,
                    'monster_health'   => 0,
                ]
            ]);
        }

        $characterHealth = $this->monsterPlayerFight->getCharacterHealth();
        $monsterHealth   = $this->monsterPlayerFight->getMonsterHealth();
        $characterHealth = $characterHealth <= 0 ? 0 : $characterHealth;

        if ($characterHealth <= 0) {
            $this->moveCelestial($character, $celestialFight);

            $this->battleEventHandler->processDeadCharacter($character);
        }

        $characterInCelestialFight->update([
            'character_current_health' => $this->monsterPlayerFight->getCharacterHealth(),
        ]);


        if ($characterHealth > 0 && $monsterHealth > 0) {
            $this->moveCelestial($character, $celestialFight);

            event(new UpdateCelestialFight($character->name, $this->monsterPlayerFight));
        }

        return $this->successResult([
            'logs'      => $this->monsterPlayerFight->getBattleMessages(),
            'health' => [
                'character_health' => $characterHealth,
                'monster_health'   => $this->monsterPlayerFight->getMonsterHealth(),
            ]
        ]);
    }

    public function revive(Character $character) {
        $character = $this->battleEventHandler->processRevive($character);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $celestialFight            = CelestialFight::find($characterInCelestialFight->celestial_fight_id);

        return $this->successResult([
            'fight' => [
                'character' =>[
                    'max_health'     => $characterInCelestialFight->character_max_health,
                    'current_health' => $characterInCelestialFight->character_current_health,
                ],
                'monster' => [
                    'max_health'     => $celestialFight->max_health,
                    'current_health' => $celestialFight->current_health,
                ],
            ],
        ]);
    }

    protected function handleMonsterDeath(Character $character, CelestialFight $celestialFight) {
        event(new UpdateCelestialFight($character->name, $this->monsterPlayerFight));

        $character = $this->timeOutCelestialEvent($character);

        $this->giveShards($character, $celestialFight);

        BattleAttackHandler::dispatch($character->id, $celestialFight->monster_id)->onQueue('default_long')->delay(now()->addSeconds(2));

        $celestialFightType = new CelestialConjureType($celestialFight->type);

        if ($celestialFightType->isPublic()) {
            event(new GlobalMessageEvent($character->name . ' has slain the ' . $celestialFight->monster->name . '! They have been rewarded with a godly gift!'));
        } else {
            event(new ServerMessageEvent($character->user,'You have slain the ' . $celestialFight->monster->name . '! They have been rewarded with a godly gift!'));
        }

        $this->characterCacheData->deleteCharacterSheet($character);

        CharacterInCelestialFight::where('celestial_fight_id', $celestialFight->id)->delete();

        $celestialFight->delete();
    }

    protected function timeOutCelestialEvent(Character $character): Character {
        $timeLeft = now()->addSeconds(10);

        $character->update([
            'can_engage_celestials'          => false,
            'can_engage_celestials_again_at' => $timeLeft,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        broadcast(new UpdateCharacterCelestialTimeOut($character->user, 10));

        CelestialTimeOut::dispatch($character)->delay(10);

        return $character->refresh();
    }

    protected function giveShards(Character $character, CelestialFight $celestialFight) {

        $monsterShards = $celestialFight->monster->shards;
        $monsterShards = $monsterShards + $monsterShards * $this->getShardBonus($character);

        $shards = $character->shards + $monsterShards;

        if ($shards >= MaxCurrenciesValue::MAX_SHARDS) {
            $shards = MaxCurrenciesValue::MAX_SHARDS;
        }

        $character->update([
            'shards' => $shards,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'You received: ' . number_format($monsterShards) . ' shards! Shards can only be used in Alchemy.'));
    }

    protected function updateCharacterInFight(Character $character, CharacterInCelestialFight $characterInCelestialFight) {
        $health = $this->characterCacheData->getCachedCharacterData($character, 'health');

        $characterInCelestialFight->update([
            'character_max_health'    => $health,
            'character_current_health'=> $health,
        ]);

        return $characterInCelestialFight->refresh();
    }

    protected function moveCelestial(Character $character, CelestialFight $celestialFight) {
        $monster = $celestialFight->monster;

        $celestialFight->update([
            'x_position'      => CoordinatesCache::getFromCache()['x'][rand(CoordinatesCache::getFromCache()['x'][0], (count(CoordinatesCache::getFromCache()['x']) - 1))],
            'y_position'      => CoordinatesCache::getFromCache()['y'][rand(CoordinatesCache::getFromCache()['y'][0], (count(CoordinatesCache::getFromCache()['y']) - 1))],
            'current_health'  => $celestialFight->current_health,
        ]);

        $celestialFightType = new CelestialConjureType($celestialFight->type);

        if ($celestialFightType->isPublic()) {
            event(new GlobalMessageEvent($character->name . ' Has caused: ' . $monster->name . ' to flee to the far ends of Tlessa (use /pct or /pc to find the new coordinates).'));
        } else {
            event(new ServerMessageEvent($character->user, 'You Have caused: ' . $monster->name . ' to flee to the far ends of Tlessa (use /pct or /pc to find the new coordinates).'));
        }
    }
}
