<?php

namespace App\Game\Core\Traits;


use App\Flare\Events\ServerMessageEvent;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\CharacterService;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

Trait HandleCharacterLevelUp {

    /**
     * Handle possible level up.
     *
     * @param Character $character
     * @return Character
     */
    public function handlePossibleLevelUp(Character $character): Character {
        if ($character->xp >= $character->xp_next) {
            $leftOverXP = $character->xp - $character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleLevelUps($character, $leftOverXP);
            }

            if ($leftOverXP <= 0) {
                $this->handleCharacterLevelUp($character,0);
            }
        }

        return $character->refresh();
    }

    /**
     * Handle instances where we could have multiple level ups.
     *
     * @param Character $character
     * @param int $leftOverXP
     * @return Character
     */
    public function handleLevelUps(Character $character, int $leftOverXP): Character {
        $this->handleCharacterLevelUp($character, $leftOverXP);

        if ($leftOverXP >= $character->xp_next) {
            $leftOverXP = $character->xp - $character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleLevelUps($character, $leftOverXP);
            }

            if ($leftOverXP <= 0) {
                $this->handleLevelUps($character,0);
            }
        }

        if ($leftOverXP < $character->xp_next) {
            $character->update([
                'xp' => $leftOverXP
            ]);
        }

        return $character->refresh();
    }

    /**
     * Handle character level up.
     *
     * @param Character $character
     * @param int $leftOverXP
     * @return Character
     */
    protected function handleCharacterLevelUp(Character $character, int $leftOverXP): Character {
        resolve(CharacterService::class)->levelUpCharacter($character, $leftOverXP);

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        $this->updateCharacterStats($character);

        event(new ServerMessageEvent($character->user, 'level_up'));

        return $character;
    }

    /**
     * Update the character stats.
     *
     * @param Character $character
     * @return void
     */
    protected function updateCharacterStats(Character $character): void {
        $characterData = new Item($character, resolve(CharacterSheetBaseInfoTransformer::class));
        $characterData = resolve(Manager::class)->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }
}
