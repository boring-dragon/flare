<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Models\Character;

class CharacterTopBarTransformer extends BaseTransformer {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {

        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character);

        return [
            'attack'            => $characterStatBuilder->buildTotalAttack(),
            'health'            => $characterStatBuilder->buildHealth(),
            'ac'                => $characterStatBuilder->buildDefence(),
            'level'             => number_format($character->level),
            'max_level'         => number_format($this->getMaxLevel($character)),
            'xp'                => (int) $character->xp,
            'xp_next'           => (int) $character->xp_next,
            'str_modded'        => $characterStatBuilder->statMod('str'),
            'dur_modded'        => $characterStatBuilder->statMod('dur'),
            'dex_modded'        => $characterStatBuilder->statMod('dex'),
            'chr_modded'        => $characterStatBuilder->statMod('chr'),
            'int_modded'        => $characterStatBuilder->statMod('int'),
            'agi_modded'        => $characterStatBuilder->statMod('agi'),
            'focus_modded'      => $characterStatBuilder->statMod('focus'),
            'inventory_max'     => $character->inventory_max,
            'inventory_count'   => $character->getInventoryCount(),
            'gold'              => number_format($character->gold),
            'gold_dust'         => number_format($character->gold_dust),
            'shards'            => number_format($character->shards),
            'copper_coins'      => number_format($character->copper_coins),
            'is_silenced'       => $character->user->is_silenced,
            'can_talk_again_at' => $character->user->can_talk_again_at,
            'force_name_change' => $character->force_name_change,
            'is_banned'         => $character->user->is_banned,
        ];
    }
}
