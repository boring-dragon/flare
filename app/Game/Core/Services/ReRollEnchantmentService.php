<?php

namespace App\Game\Core\Services;

use App\Flare\Builders\AffixAttributeBuilder;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateQueenOfHeartsPanel;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class ReRollEnchantmentService {

    private AffixAttributeBuilder $affixAttributeBuilder;

    private RandomEnchantmentService $randomEnchantmentService;

    private int $goldDust;

    private int $shardCost;

    private $functionMap = [
        'base'       => [
            'setCoreModifiers',
            'setClassBonus',
        ],
        'stats'      => [
            'increaseStats',
            'reduceEnemyStats',
        ],
        'skills'     => [
            'setSkillDetails',
            'setSkillBonuses',
        ],
        'damage'     => [
            'setDamageDetails',
            'setDevouringLight',
            'setLifeStealingAmount',
            'setEntrancingAmount',
        ],
        'resistance' => [
            'setReductions',
        ],
    ];

    public function __construct(AffixAttributeBuilder $affixAttributeBuilder, RandomEnchantmentService $randomEnchantmentService) {
        $this->affixAttributeBuilder    = $affixAttributeBuilder;
        $this->randomEnchantmentService = $randomEnchantmentService;
    }

    public function canAfford(Character $character, string $type, string $selectedAffix): bool {
        $goldDust = $this->getGoldDustCost($character, $type, $selectedAffix);
        $shards   = $this->getShardsCost($character, $type, $selectedAffix);

        return $character->gold_dust > $goldDust && $character->shards > $shards;
    }

    protected function getGoldDustCost(Character $character, string $type, string $selectedAffix): int {
        $goldDust = 10000;

        if ($selectedAffix === 'all-enchantments') {
            $goldDust *= 2;
        }

        if ($type === 'everything') {
            $goldDust += 500;
        } else {
            $goldDust += 100;
        }

        return $this->goldDust = $goldDust;
    }

    protected function getShardsCost(Character $character, string $type, string $selectedAffix): int {
        $shardCost = 100;

        if ($selectedAffix === 'all-enchantments') {
            $shardCost *= 2;
        }

        if ($type === 'everything') {
            $shardCost += 250;
        } else {
            $shardCost += 100;
        }

        return $this->shardCost = $shardCost;
    }

    public function reRoll(Character $character, InventorySlot $slot, string $affixType, string $reRollType) {
        $character->update([
            'gold_dust' => $character->gold_dust - $this->goldDust,
            'shards'    => $character->shards - $this->shardCost,
        ]);

        $duplicateItem = $this->doReRoll($character, $slot->item, $affixType, $reRollType);

        $slot->update([
            'item_id' => $duplicateItem->id,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'Ooooh hoo hoo hoo! I have done it, child! I have made the modifications and I think you\'ll be happy! Oh child, I am so happy! Ooh hoo hoo hoo!'));
    }

    public function doReRoll(Character $character, Item $item, string $affixType, string $reRollType) {
        $duplicateItem   = $item->duplicate();

        $duplicateItem   = $this->applyHolyStacks($item, $duplicateItem);

        $affixes = $this->fetchAffixesForReRoll($duplicateItem, $affixType);

        foreach ($affixes as $affix) {
            $this->changeAffix($character, $duplicateItem, $affix, $reRollType);
        }

        $duplicateItem = $duplicateItem->refresh();

        $duplicateItem->update([
            'market_sellable' => true,
            'is_mythic'       => $item->is_mythic,
        ]);

        return $duplicateItem->refresh();
    }

    public function canAffordMovementCost(Character $character, int $selectedItemToMoveId, string $selectedAffix) {
        $costs = $this->getMovementCosts($selectedItemToMoveId, $selectedAffix);

        return $character->gold_dust >= $costs['gold_dust_cost'] && $character->shards >= $costs['shards_cost'];
    }

    public function getMovementCosts(int $selectedItemToMoveId, string $selectedAffix): array {
        $item = Item::find($selectedItemToMoveId);

        if (is_null($item)) {
            return false;
        }

        $cost = 0;

        if ($selectedAffix === 'all-enchantments') {
            if (!is_null($item->item_prefix_id)) {
                $cost += $item->itemPrefix->cost;
            }

            if (!is_null($item->item_suffix_id)) {
                $cost += $item->itemSuffix->cost;
            }
        } else {
            $cost += ItemAffix::find($item->{'item_' . $selectedAffix . '_id'})->cost;
        }

        $cost      = $cost / 1000000;
        $shardCost = $cost * .005;

        $shardCost = (int) round($shardCost);

        return [
            'gold_dust_cost' => $cost,
            'shards_cost'    => $shardCost,
        ];
    }

    public function moveAffixes(Character $character, InventorySlot $slot, InventorySlot $secondarySlot, string $affixType) {
        $costs = $this->getMovementCosts($slot->item_id, $affixType);

        $character->update([
            'gold_dust' => $character->gold_dust - $costs['gold_dust_cost'],
            'shards'    => $character->shards - $costs['shards_cost'],
        ]);

        $duplicateSecondaryItem = $secondarySlot->item->duplicate();
        $duplicateUnique        = $slot->item->duplicate();
        $duplicateSecondaryItem = $this->applyHolyStacks($slot->item, $duplicateSecondaryItem);

        $duplicateUnique->update([
            'market_sellable' => true,
        ]);

        $deletedAll  = false;
        $deletedSome = false;
        $deletedNone = false;

        if ($affixType === 'all-enchantments') {
            $deletedOne  = false;
            $deletedTwo  = false;


            if (!is_null($slot->item->item_suffix_id)) {
                if ($slot->item->itemSuffix->randomly_generated) {
                    $duplicateSecondaryItem->update([
                        'item_suffix_id' => $slot->item->item_suffix_id,
                    ]);

                    $duplicateUnique->update([
                        'item_suffix_id' => null,
                    ]);

                    $deletedOne = true;
                }
            }

            if (!is_null($slot->item->item_prefix_id)) {
                if ($slot->item->itemPrefix->randomly_generated) {
                    $duplicateSecondaryItem->update([
                        'item_prefix_id' => $slot->item->item_prefix_id,
                    ]);

                    $duplicateUnique->update([
                        'item_prefix_id' => null,
                    ]);

                    $deletedTwo = true;
                }
            }

            if ($deletedOne && $deletedTwo) {
                $slot->delete();
                $duplicateUnique->delete();

                $deletedAll = true;
            } else {
                $slot->update([
                    'item_id' => $duplicateUnique->id,
                ]);

                $deletedSome = true;
            }
        } else {
            $duplicateSecondaryItem->update([
                'item_'.$affixType.'_id' => $slot->item->{'item_'.$affixType.'_id'},
            ]);

            $duplicateUnique->update([
                'item_'.$affixType.'_id' => null,
            ]);

            $duplicateUnique = $duplicateUnique->refresh();

            if (is_null($duplicateUnique->itemSuffix) && is_null($duplicateUnique->itemPrefix)) {
                $duplicateUnique->delete();
                $slot->delete();

                $deletedSome = true;
            } else {
                $slot->update([
                    'item_id' => $duplicateUnique->id
                ]);

                $deletedNone = true;
            }
        }

        $duplicateSecondaryItem->update([
            'is_market_sellable' => true,
            'is_mythic'          => $slot->item->is_mythic,
        ]);

        $secondarySlot->update([
            'item_id' => $duplicateSecondaryItem->id,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'Ooooh hoo hoo hoo! I have done as thou have requested, my lovely, beautiful, gorgeous child! Oh look at how powerful you are!'));

        if ($deletedAll) {
            event(new GlobalMessageEvent($character->name . ' Makes the Queen of Hearts glow so bright, thousands of demons in Hell are banished by her beauty and power alone!'));
        }

        if ($deletedSome) {
            event(new GlobalMessageEvent($character->name . ' Makes the Queen of Hearts laugh! She is falling in love!'));
        }

        if ($deletedNone) {
            event(new GlobalMessageEvent($character->name . ' Makes the Queen of Hearts blush! She is attracted to them now.'));
        }

        $slot = $secondarySlot->refresh();

        event(new ServerMessageEvent($character->user, 'The Queen has moved the affixes and created the item: ' . $slot->item->affix_name, $slot->id));
    }

    /**
     * Apply the old items holy stacks to the new item.
     *
     * @param Item $oldItem
     * @param Item $item
     * @return Item
     */
    protected function applyHolyStacks(Item $oldItem, Item $item): Item {
        if ($oldItem->appliedHolyStacks()->count() > 0) {

            foreach ($oldItem->appliedHolyStacks as $stack) {
                $stackAttributes = $stack->getAttributes();

                $stackAttributes['item_id'] = $item->id;

                $item->appliedHolyStacks()->create($stackAttributes);
            }
        }

        return $item->refresh();
    }

    protected function fetchAffixesForReRoll(Item $item, string $affixType): array {
        $affixes = [];

        if ($affixType === 'all-enchantments') {

            if (!is_null($item->item_prefix_id)) {
                if ($item->itemPrefix->randomly_generated) {
                    $affixes[] = $item->itemPrefix;
                }
            }

            if (!is_null($item->item_suffix_id)) {
                if ($item->itemSuffix->randomly_generated) {
                    $affixes[] = $item->itemSuffix;
                }
            }
        } else {
            $affixes[] = $item->{'item' . ucfirst($affixType)};
        }

        return $affixes;
    }

    protected function changeAffix(Character $character, Item $item, ItemAffix $itemAffix, string $changeType) {
        $amountPaid             = new RandomAffixDetails($itemAffix->cost);

        $affixAttributeBuilder = $this->affixAttributeBuilder->setPercentageRange($amountPaid->getPercentageRange())
                                                              ->setDamageRange($amountPaid->getDamageRange())
                                                              ->setCharacterSkills($character->skills);
        if ($changeType === 'everything') {
            $changes = $affixAttributeBuilder->buildAttributes($itemAffix->type, $itemAffix->cost);

            unset($changes['name']);
        } else {
            $changes = [];

            foreach ($this->functionMap[$changeType] as $functionName) {
                $changes = array_merge($changes, $affixAttributeBuilder->{$functionName}());
            }
        }

        $duplicateAffix = $itemAffix->duplicate();

        $duplicateAffix->update($changes);

        $item->update(['item_' . $itemAffix->type . '_id' => $duplicateAffix->id]);
    }
}
