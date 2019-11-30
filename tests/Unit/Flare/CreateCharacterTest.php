<?php

namespace Tests\Unit\Flare;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use App\Flare\Builders\CharacterBuilder;

class CreateCharacterTest extends TestCase
{

    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateItem,
        CreateUser;

    public function setUp(): void {
        parent::setup();

        $this->createItem([
            'name' => 'Rusty Dagger',
        ]);
    }

    public function testCreateCharacter()
    {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $character = resolve(CharacterBuilder::class)->setRace($race)
                                                     ->setClass($class)
                                                     ->createCharacter($this->createUser(), 'sample')
                                                     ->assignSkills()
                                                     ->character();

        $this->assertEquals('sample', $character->name);
        $this->assertEquals(8, $character->str);
        $this->assertEquals(8, $character->dex);
        $this->assertEquals('dex', $character->damage_stat);
        $this->assertEquals($race->name, $character->race->name);
        $this->assertEquals($class->name, $character->class->name);
    }

    public function testCreateCharacterWithSkills() {
        $race = $this->createRace([
            'str_mod' => 3,
            'accuracy_mod' => 2,
            'dodge_mod' => 2
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
            'accuracy_mod' => 0,
            'dodge_mod' => 1,
        ]);

        $character = resolve(CharacterBuilder::class)->setRace($race)
                                                     ->setClass($class)
                                                     ->createCharacter($this->createUser(), 'sample')
                                                     ->assignSkills()
                                                     ->character();

        $this->assertEquals(2, $character->skills->where('name', '=', 'Accuracy')->first()->skill_bonus);
        $this->assertEquals(3, $character->skills->where('name', '=', 'Dodge')->first()->skill_bonus);
    }
}
