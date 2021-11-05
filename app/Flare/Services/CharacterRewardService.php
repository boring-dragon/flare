<?php

namespace App\Flare\Services;

use App\Flare\Calculators\XPCalculator as CalculatorsXPCalculator;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Values\MaxCurrenciesValue;
use Facades\App\Flare\Calculators\XPCalculator;

class CharacterRewardService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var CharacterXPService $characterXpService
     */
    private $characterXpService;

    /**
     * Constructor
     *
     * @param Character $character
     * @param CharacterXPService $characterXpService
     */
    public function __construct(Character $character, CharacterXPService $characterXpService) {
        $this->character          = $character;
        $this->characterXpService = $characterXpService;
    }

    /**
     * Distribute the gold and xp to the character.
     *
     * @param Monster $monster
     * @param Adventure $adventure | null
     * @return void
     */
    public function distributeGoldAndXp(Monster $monster, Adventure $adventure = null) {
        $currentSkill = $this->fetchCurrentSkillInTraining();
        $xpReduction  = 0.0;
        $gameMap      = $this->character->map->gameMap;

        if (!is_null($currentSkill)) {
            $xpReduction = $currentSkill->xp_towards;

            $this->trainSkill($currentSkill, $adventure, $monster);
        }

        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level, $xpReduction);
        $xp = $this->characterXpService->determineXPToAward($this->character, $xp);

        if (!is_null($gameMap->xp_bonus)) {
            $xp = $xp * (1 + $gameMap->xp_bonus);
        }

        $this->character->xp += $xp;
        $newGold              = $this->character->gold + $monster->gold;

        $maxCurrencies = new MaxCurrenciesValue($newGold, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $this->character->save();

            return;
        }

        $this->character->gold += $monster->gold;

        $this->character->save();
    }

    /**
     * Get the refreshed Character
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    /**
     * Get the skill in trainind or null
     *
     * @return mixed
     */
    public function fetchCurrentSkillInTraining() {
        return $this->character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();
    }

    /**
     * Fire the update skill event.
     *
     * @param Skill $skill
     * @param Adventure $adventure | nul
     * @return void
     */
    public function trainSkill(Skill $skill, Adventure $adventure = null, Monster $monster = null) {
        event(new UpdateSkillEvent($skill, $adventure, $monster));
    }
}
