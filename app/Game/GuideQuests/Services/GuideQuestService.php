<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class GuideQuestService {

    private RandomItemDropBuilder $randomItemDropBuilder;

    public function __construct(RandomItemDropBuilder $randomItemDropBuilder) {
        $this->randomItemDropBuilder = $randomItemDropBuilder;
    }

    public function fetchQuestForCharacter(Character $character): GuideQuest | null {
        $lastCompletedGuideQuest = $character->questsCompleted()->orderByDesc('guide_quest_id')->first();

        if (is_null($lastCompletedGuideQuest)) {
            $quest = GuideQuest::first();
        } else {
            $questId = GuideQuest::where('id', '>', $lastCompletedGuideQuest->guide_quest_id)->min('id');
            $quest   = GuideQuest::find($questId);
        }

        if (is_null($quest)) {
            return null;
        }

        return $quest;
    }

    public function handInQuest(Character $character, GuideQuest $quest) {
        if (!$this->canHandInQuest($character, $quest)) {
            return false;
        }

        if ($character->isInventoryFull()) {
            event(new ServerMessageEvent($character->user, 'Failed to give you quest reward item. Inventory is full.'));
        } else {
            $this->rewardItem($character);
        }

        $gold = $character->gold + ($quest->reward_level * 1000);

        if ($gold >= MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        event(new ServerMessageEvent($character->user, 'Rewarded with: ' . number_format(($quest->reward_level * 1000)) . ' Gold.'));

        $character->update(['gold' => $gold]);

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $quest->id,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        return true;
    }

    public function canHandInQuest(Character $character, GuideQuest $quest): bool {
        $alreadyCompleted = $character->questsCompleted()->where('guide_quest_id', $quest->id)->first();

        if (!is_null($alreadyCompleted)) {
            return false;
        }

        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING)->get()->isNotEmpty()) {
            return false;
        }

        $canHandIn = false;

        if (!is_null($quest->required_level)) {
            $canHandIn = $character->level >= $quest->required_level;
        }

        if ($quest->required_skill !== null) {
            $canHandIn = $character->skills()->where('game_skill_id', $quest->required_skill)->first()->level >= $quest->required_skill_level;
        }

        if (!is_null($quest->required_faction_id)) {
            $faction = $character->factions()->where('game_map_id', $quest->required_faction_id)->first();

            $canHandIn = $faction->current_level >= $quest->required_faction_level;
        }

        if (!is_null($quest->required_game_map_id)) {
            $gameMap = GameMap::find($quest->required_game_map_id);

            $canHandIn = $character->inventory->slots->filter(function($slot) use($gameMap) {
                return $slot->item->type === 'quest' && $slot->item->id === $gameMap->map_required_item->id;
            })->isNotEmpty();
        }

        if (!is_null($quest->required_quest_id)) {
            $canHandIn = !is_null($character->questsCompleted()->where('quest_id', $quest->id)->first());
        }

        if (!is_null($quest->required_quest_item_id)) {
            $canHandIn = $character->inventory->slots->filter(function($slot) use($quest) {
                return $slot->item->type === 'quest' && $slot->item->id === $quest->required_quest_item_id;
            })->isNotEmpty();
        }

        return $canHandIn;
    }

    protected function rewardItem(Character $character): Character {

        $level = $character->level / 100;

        if ($level < 1) {
            $level = 1;
        }

        $fetchItem = Item::whereNotIn('type', ['quest', 'alchemy', 'trinket'])
                         ->whereNull('item_prefix_id')
                         ->whereNull('item_suffix_id')
                         ->where('skill_level_required', '<=', $level)
                         ->inRandomOrder()
                         ->first();

        if (!is_null($fetchItem)) {
            $fetchSuffix = ItemAffix::where('skill_level_required', '<=', $level)
                ->where('type', 'suffix')
                ->orderByDesc('skill_level_required')
                ->first();

            $fetchPrefix = ItemAffix::where('skill_level_required', '<=', $level)
                ->where('type', 'prefix')
                ->orderByDesc('skill_level_required')
                ->first();

            $fetchItem = $fetchItem->duplicate();

            $fetchItem->update([
                'item_suffix_id' => $fetchSuffix->id,
                'item_prefix_id' => $fetchPrefix->id,
            ]);

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $fetchItem->id,
            ]);

            $slot = $character->refresh()->inventory->slots()->where('item_id', $fetchItem->id)->first();

            event(new ServerMessageEvent($character->user, 'The Guide rewarded you with: ' . $fetchItem->affix_name . '. (Its the enchantments you care about child ...)', $slot->id));
        }

        return $character->refresh();
    }
}
