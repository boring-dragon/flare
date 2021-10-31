<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Skill;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Messages\Events\GlobalMessageEvent;

class AdventureRewardService {

    use CanHaveQuestItem;

    /**
     * @var CharacterService $characterService
     */
    private $characterService;

    private $buildCharacterAttackTypes;

    /**
     * @var array $messages
     */
    private $messages = [];

    /**
     * @var array $itemsLeft
     */
    private $itemsLeft = [];

    /**
     * @param CharacterService $characterService
     * @return void
     */
    public function __construct(CharacterService $characterService, BuildCharacterAttackTypes $buildCharacterAttackTypes) {

        $this->characterService          = $characterService;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

    /**
     * Distribute the rewards
     *
     * @param array $rewards
     * @param Character $character
     * @return AdventureRewardService
     */
    public function distributeRewards(array $rewards, Character $character): AdventureRewardService {
        $character->gold += $rewards['gold'];
        $character->save();

        $this->handleXp($rewards['exp'], $character);
        $this->handleSkillXP($rewards, $character);

        if (!empty($rewards['items'])) {
            $this->handleItems($rewards['items'], $character);
        }

        return $this;
    }

    /**
     * Get messages for display
     *
     * @return array
     */
    public function getMessages(): array {
        return $this->messages;
    }

    public function getItemsLeft(): array {
        return $this->itemsLeft;
    }

    protected function handleXp(int $xp, Character $character): void {
        $totalLevels = floor($xp / 100);
        $oldXP       = $character->xp;

        if ($totalLevels > 0) {

            for ($i = 1; $i <= $totalLevels; $i++) {
                $this->giveXP(100, $character);

                $character = $character->refresh();
            }

            $leftOver = $xp - $totalLevels * 100;

            $this->giveXP($oldXP + $leftOver, $character);

            return;
        }

        $this->giveXP($oldXP + $xp, $character);
    }

    protected function giveXP(int $xp, Character $character) {
        $character->xp += $xp;
        $character->save();

        if ($character->xp >= $character->xp_next) {
            $this->characterService->levelUpCharacter($character);

            $character = $character->refresh();

            $this->buildCharacterAttackTypes->buildCache($character);

            $this->messages[] = 'You gained a level! Now level: ' . $character->level;
        }
    }

    protected function handleSkillXP(array $rewards, Character $character): void {
        if (isset($rewards['skill'])) {
            $skill = $character->skills->filter(function($skill) use($rewards) {
                return $skill->name === $rewards['skill']['skill_name'];
            })->first();

            if (is_null($skill)) {
                return;
            }

            $xp = $rewards['skill']['exp'];

            $totalLevels = floor($xp / $skill->xp_max);
            $oldXP = $skill->xp;

            if ($totalLevels > 0) {

                for ($i = 1; $i <= $totalLevels; $i++) {
                    $this->giveSkillXP(100, $skill);

                    $skill = $skill->refresh();
                }

                $leftOver = $xp - $totalLevels * 100;

                $this->giveSkillXP($oldXP + $leftOver, $skill);

                return;
            }

            $this->giveSkillXP($oldXP + $xp, $skill);
        }
    }

    protected function giveSkillXP(int $xp, Skill $skill) {
        $skill->update([
            'xp' => $xp
        ]);

        $skill = $skill->refresh();

        if ($skill->xp >= $skill->xp_max) {
            if ($skill->level <= $skill->max_level) {
                $level      = $skill->level + 1;

                $skill->update([
                    'level'              => $level,
                    'xp_max'             => $skill->can_train ? rand(100, 150) : rand(100, 200),
                    'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                    'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                    'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                    'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                    'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                    'skill_bonus'        => $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level,
                    'xp'                 => 0,
                ]);

                $this->messages[] = 'Your skill: ' . $skill->name . ' gained a level and is now level: ' . $skill->level;
            }
        }
    }

    protected function handleItems(array $items, Character $character): void {
        $character = $character->refresh();
        $newItemList = $items;

        if (!empty($items)) {
            foreach ($items as $index => $item) {
                $item = Item::find($item['id']);

                if (!is_null($item)) {
                    if ($character->isInventoryFull()) {
                        $this->messages['error'] = 'Your inventory is full. You must clear some space, come back and finish collecting the remaining items.';

                        $this->itemsLeft = $newItemList;

                        return;
                    }

                    if ($item->type === 'quest') {
                        if ($this->canHaveItem($character, $item)) {
                            $character->inventory->slots()->create([
                                'inventory_id' => $character->inventory->id,
                                'item_id'      => $item->id,
                            ]);

                            $message = $character->name . ' has found: ' . $item->affix_name;

                            broadcast(new GlobalMessageEvent($message));

                            $this->messages[] = 'You gained the item: ' . $item->affix_name;
                        }
                    } else {
                        $character->inventory->slots()->create([
                            'inventory_id' => $character->inventory->id,
                            'item_id'      => $item->id,
                        ]);

                        $this->messages[] = 'You gained the item: ' . $item->affix_name;
                    }

                    // Remove the item.
                    unset($newItemList[$index]);
                } else {
                    $this->messages[] = 'You failed to gain the item: Item no longer exists.';
                }

                $character = $character->refresh();
            }
        }
    }
}
