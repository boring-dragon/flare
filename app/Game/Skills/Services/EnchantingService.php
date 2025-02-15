<?php

namespace App\Game\Skills\Services;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Inventory;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\Traits\UpdateCharacterGold;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;

class EnchantingService {

    use ResponseBuilder, UpdateCharacterGold;

    /**
     * @var CharacterStatBuilder $characterStatBuilder;
     */
    private CharacterStatBuilder $characterStatBuilder;

    /**
     * @var CharacterInventoryService $characterInventoryService
     */
    private $characterInventoryService;

    /**
     * @var EnchantItemService $enchantItemService
     */
    private $enchantItemService;

    /**
     * @var RandomEnchantmentService
     */
    private $randomEnchantmentService;

    /**
     * @var bool $sentToEasyMessage
     */
    private $sentToEasyMessage = false;

    /**
     * Only set if the affix to be applied was too easy to enchant.
     *
     * @var bool $wasTooEasy
     */
    private $wasTooEasy = false;

    /**
     * Constructor
     *
     * @param CharacterStatBuilder $characterStatBuilder
     * @param CharacterInventoryService $characterInventoryService
     * @param EnchantItemService $enchantItemService
     * @param RandomEnchantmentService $randomEnchantmentService
     */
    public function __construct(CharacterStatBuilder $characterStatBuilder,
                                CharacterInventoryService $characterInventoryService,
                                EnchantItemService $enchantItemService,
                                RandomEnchantmentService $randomEnchantmentService)
    {

        $this->characterStatBuilder        = $characterStatBuilder;
        $this->characterInventoryService   = $characterInventoryService;
        $this->enchantItemService          = $enchantItemService;
        $this->randomEnchantmentService    = $randomEnchantmentService;
    }

    /**
     * Fetches the affixes for a character.
     *
     * Only returns that which the player has the skill level and intelligence for.
     *
     * @param Character $character
     * @param bool $ignoreTrinkets
     * @return array
     */
    public function fetchAffixes(Character $character, bool $ignoreTrinkets = false): array {
        $characterInfo   = $this->characterStatBuilder->setCharacter($character);
        $enchantingSkill = $this->getEnchantingSkill($character);

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);
        $inventory                 = $characterInventoryService->getInventoryForType('inventory');

        if ($ignoreTrinkets) {
            $inventory = array_values(array_filter($inventory, function($item) {
                return $item['type'] !== 'trinket';
            }));
        }

        $newInventory = [];

        foreach ($inventory as $item) {
            if ($item['attached_affixes_count'] === 0) {
                array_unshift($newInventory, $item);
            } else {
                $newInventory[] = $item;
            }
        }

        return [
            'affixes'             => $this->getAvailableAffixes($characterInfo, $enchantingSkill),
            'character_inventory' => $newInventory,
        ];
    }

    /**
     * Does the cost supplied actually match the actual cost?
     *
     * @param array $enchantmentIds
     * @param int $itemId
     * @return int
     */
    public function getCostOfEnchantment(array $enchantmentIds, int $itemId): int {
        $itemAffixes   = ItemAffix::findMany($enchantmentIds);
        $itemToEnchant = Item::find($itemId);

        if (is_null($itemAffixes)) {
            return 0;
        }

        if (is_null($itemToEnchant)) {
            return 0;
        }

        $cost = $itemAffixes->sum('cost');

        foreach ($itemAffixes as $itemAffix) {
            if (!is_null($itemToEnchant->{'item_' . $itemAffix->type . '_id'})) {
                $cost += 1000;
            }
        }

        return $cost;
    }

    /**
     * Enchant an item.
     *
     * Attempts to enchant an item with the supplied affixes and slot.
     *
     * The params passed in must be the request params coming back from the request.
     *
     * The array returned contains the status and the details, either a list of
     * the characters inventory and their affixes they can enchant or a error message.
     *
     * eg, ['message' => '', 'status' => 422] or
     * ['affixes' => Collection, 'character_inventory' => [...], 'status' => 200]
     *
     * @param Character $character
     * @param array $params
     * @param InventorySlot $slot
     * @param int $cost
     * @return void
     */
    public function enchant(Character $character, array $params, InventorySlot $slot, int $cost): void {
        $enchantingSkill = $this->getEnchantingSkill($character);

        $this->updateCharacterGold($character, $cost);

        $this->attachAffixes($params['affix_ids'], $slot, $enchantingSkill, $character);

        $this->enchantItemService->updateSlot($slot);
    }

    public function timeForEnchanting(Item $item) {

        if (!is_null($item->itemPrefix) && !is_null($item->itemSuffix)) {
            return 'triple';
        }

        if (!is_null($item->itemPrefix) || !is_null($item->itemSuffix)) {
            return 'double';
        }

        return null;
    }

    public function getSlotFromInventory(Character $character, int $slotId) {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('id', $slotId)->where('inventory_id', $inventory->id)->where('equipped', false)->first();
    }

    protected function getEnchantingSkill(Character $character): Skill {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING)->first();

        return Skill::where('character_id', $character->id)->where('game_skill_id', $gameSkill->id)->first();
    }

    protected function getAvailableAffixes(CharacterStatBuilder $builder, Skill $enchantingSkill): Collection {

        $currentInt = $builder->statMod('int');

        // If the current intelligence is over 1 billion,
        // set the max to 1 billion as enchanting will never go over this.
        if ($currentInt > 1000000000) {
            $currentInt = 1000000000;
        }

        return ItemAffix::select('name', 'cost', 'id', 'type')
                        ->where('int_required', '<=', $currentInt)
                        ->where('skill_level_required', '<=', $enchantingSkill->level)
                        ->where('randomly_generated', false)
                        ->orderBy('skill_level_required', 'asc')
                        ->get();
    }

    protected function attachAffixes(array $affixes, InventorySlot $slot, Skill $enchantingSkill, Character $character) {
        foreach ($affixes as $affixId) {
            $slot  = $slot->refresh();

            $affix = ItemAffix::find($affixId);

            if (is_null($affix)) {
                continue;
            }

            // Reset.
            $this->wasTooEasy = false;

            if ($enchantingSkill->level < $affix->skill_level_required) {
                event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));

                return;
            }

            if ($enchantingSkill->level >= $affix->skill_level_trivial) {
                if (!$this->sentToEasyMessage) {
                    event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));
                    $this->sentToEasyMessage = true;
                }

                $this->processedEnchant($slot, $affix, $character, $enchantingSkill, true);

                $this->wasTooEasy = true;
            }

            /**
             * If the affix wasn't too easy to attach, attempt to enchant with the difficulty check
             * in place.
             *
             * If we fail to do this then we return from the loop.
             */
            if (!$this->wasTooEasy) {
                if (!$this->processedEnchant($slot, $affix, $character, $enchantingSkill)) {

                    return;
                }
            }
        }
    }

    protected function processedEnchant(InventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false) {
        $enchanted = $this->enchantItemService->attachAffix($slot->item, $affix, $enchantingSkill, $tooEasy);

        if ($enchanted) {
            $this->appliedEnchantment($slot, $affix, $character, $enchantingSkill, $tooEasy);
        } else {
            $this->failedToApplyEnchantment($slot, $affix, $character);

            return false;
        }

        return true;
    }

    protected function appliedEnchantment(InventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false) {
        $message = 'Applied enchantment: '.$affix->name.' to: ' . $slot->item->refresh()->affix_name;

        event(new ServerMessageEvent($character->user, 'enchanted', $message, $slot->id));

        if (!$tooEasy) {
            event(new UpdateSkillEvent($enchantingSkill));
        }
    }

    protected function failedToApplyEnchantment(InventorySlot $slot, ItemAffix $affix, Character $character) {
        $message = 'You failed to apply '.$affix->name.' to: ' . $slot->item->refresh()->affix_name . '. The item shatters before you. You lost the investment.';

        event(new ServerMessageEvent($character->user, 'enchantment_failed', $message));

        $this->enchantItemService->deleteSlot($slot);
    }
}
