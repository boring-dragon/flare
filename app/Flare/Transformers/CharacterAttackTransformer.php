<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use Facades\App\Flare\Transformers\DataSets\CharacterAttackData;
use App\Flare\Models\Character;

class CharacterAttackTransformer extends BaseTransformer {

    /**
     * creates response data for character attack data.
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {

        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character);

        return CharacterAttackData::attackData($character, $characterStatBuilder);
    }
}
