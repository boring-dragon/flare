<?php

namespace Tests\Traits;

use App\Flare\Models\Adventure;
use App\Flare\Models\AdventureFloorDescriptions;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Game\Automation\Values\AutomationType;
use Database\Factories\AdventureFloorDescriptionFactory;
use Illuminate\Support\Str;

trait CreateCharacterAutomation {

    /**
     * @param array $details
     * @return CharacterAutomation
     */
    public function createAttackAutomation(array $details): CharacterAutomation {
        return CharacterAutomation::factory()->create(array_merge($details, [
            'type' => AutomationType::ATTACK
        ]));
    }
}
