<?php

namespace Tests\Unit\Game\PassiveSkills\Values;

use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreatePassiveSkill;

class TrainPassiveSkillTest extends TestCase {

    use RefreshDatabase, CreatePassiveSkill;

    private ?Character $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testDoNotLevelPassive() {
        $passive = $this->character->passiveSkills()->first();

        TrainPassiveSkill::dispatch($this->character, $passive);

        $passive = $passive->refresh();

        $this->assertEquals(0, $passive->current_level);
    }

    public function testLevelUpPassive() {
        $passive = $this->character->passiveSkills()->first();

        $passive->update([
            'started_at'   => now()->subMinute(),
            'completed_at' => now()->subMinute(),
        ]);

        $passive = $passive->refresh();

        TrainPassiveSkill::dispatch($this->character, $passive);

        $passive = $passive->refresh();

        $this->assertEquals(1, $passive->current_level);
    }

    public function testDoNotOverLevelPassive() {
        $passive = $this->character->passiveSkills()->first();

        $passive->update([
            'current_level' => 5,
            'started_at'    => now()->subMinute(),
            'completed_at'  => now()->subMinute(),
        ]);

        $passive = $passive->refresh();

        TrainPassiveSkill::dispatch($this->character, $passive);

        $passive = $passive->refresh();

        $this->assertEquals(5, $passive->current_level);
    }

    public function testPassiveUnlocksKingdomBuilding() {
        $character = (new CharacterFactory())->createBaseCharacter()
                                             ->givePlayerLocation()
                                             ->kingdomManagement()
                                             ->assignKingdom()
                                             ->assignBuilding([
                                                 'name' => 'Goblin Coin Bank',
                                             ], [
                                                 'is_locked' => true,
                                             ])
                                             ->getCharacter();

        $passive = $character->passiveSkills()->create([
            'character_id'      => $character->id,
            'passive_skill_id'  => $this->createPassiveSkill(array_merge([
                'effect_type' => PassiveSkillTypeValue::UNLOCKS_BUILDING,
            ], [
                'name' => $character->kingdoms->first()->buildings->first()->name
            ]))->id,
            'parent_skill_id'   => null,
            'current_level'     => 0,
            'hours_to_next'     => 1,
            'started_at'        => null,
            'completed_at'      => null,
            'is_locked'         => false,
            'started_at'        => now()->subMinute(),
            'completed_at'      => now()->subMinute(),
        ]);

        TrainPassiveSkill::dispatch($character, $passive);

        $character = $character->refresh();
        $building  = $character->kingdoms->first()->buildings->first();

        $this->assertFalse($building->is_locked);
    }
}
