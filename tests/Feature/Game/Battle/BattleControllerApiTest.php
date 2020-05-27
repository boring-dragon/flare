<?php

namespace Tests\Feature\Game\Battle;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Flare\Models\Character;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Game\Battle\Events\GoldRushCheckEvent;
use App\Game\Battle\Events\DropCheckEvent;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\DropsCheckEvent;
use App\Game\Battle\Events\ShowTimeOutEvent;
use App\Game\Battle\Events\UpdateCharacterEvent;
use App\Game\Battle\Events\UpdateTopBarEvent;
use App\Game\Battle\Events\UpdateTopBarBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateSkill;
use Tests\Setup\CharacterSetup;
use Tests\Traits\CreateItemAffix;

class BattleControllerApiTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter,
        CreateMonster,
        CreateItem,
        CreateSkill,
        CreateItemAffix;

    private $user;

    private $character;

    private $monster;

    public function setUp(): void {
        parent::setUp();

        $this->setUpMonster();

        $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'prefix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'ac_mod'               => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'suffix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'ac_mod'               => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user      = null;
        $this->character = null;
        $this->monster   = null;
    }

    public function testCanGetActions() {
        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/api/actions', [
                             'user_id' => $this->user->id
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content->monsters);
        $this->assertNotEmpty($content->monsters[0]->skills);
        $this->assertEquals($this->character->name, $content->character->data->name);
        $this->assertEquals(17, $content->character->data->attack);
    }

    public function testWhenNotLoggedInCannotGetActions() {
        $response = $this->json('GET', '/api/actions', [
                             'user_id' => 1
                         ])
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    public function testBattleResultsCharacterIsDead() {
        Queue::Fake();

        Event::fake([ServerMessageEvent::class, UpdateTopBarBroadcastEvent::class]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_character_dead' => true
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testBattleResultsMonsterIsDead() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter();

        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertTrue($currentGold !== $this->character->gold);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterLevelUp() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter([
            'xp' => 90,
            'level' => 1,
        ]);

        $this->character->refresh();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals(2, $this->character->level);
        $this->assertEquals(0, $this->character->xp);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedItem() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter([
            'looting_level' => 100,
            'looting_bonus' => 100,
        ]);

        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals(1, $this->character->level);
        $this->assertTrue($currentGold !== $this->character->gold);
        $this->assertTrue(count($this->character->inventory->slots) > 1);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterCannotGainItemBecauseInventoryIsFull() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter([
            'looting_level' => 100,
            'looting_bonus' => 100,
        ]);

        $this->character->update([
            'inventory_max' => 1,
        ]);

        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals(1, $this->character->level);
        $this->assertTrue($currentGold !== $this->character->gold);
        
        $this->assertFalse(count($this->character->inventory->slots) > 1);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedGoldRush() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter([
            'looting_level' => 100,
            'looting_bonus' => 100,
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertNotEquals(0, $this->character->gold);
    }

    public function testBattleResultsMonsterIsDeadCannotAttackAgain() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertFalse($this->character->can_attack);
    }

    public function testCharacterGetsFullXPWhenMonsterMaxLevelIsZero() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter();

        $this->monster->max_level = 0;
        $this->monster->save();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertEquals(10, $this->character->xp);

    }

    public function testCharacterGetsFullXPWhenMonsterMaxLevelIsHigherThenCharacterLevel() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter();

        $this->monster->max_level = 5;
        $this->monster->save();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertEquals(10, $this->character->xp);
    }

    public function testCharacterGetsOneThirdXPWhenMonsterMaxLevelIsLowerThenCharacterLevel() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter([
            'level' => 100,
        ]);

        $this->monster->max_level = 5;
        $this->monster->save();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertEquals(3, $this->character->xp);
    }

    public function testCharacterSeesErrorForUnknownType() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarEvent::class,
            UpdateTopBarBroadcastEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateCharacterAttackEvent::class,
        ]);

        $this->setUpCharacter([
            'level' => 100,
        ]);

        $this->monster->max_level = 5;
        $this->monster->save();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'apple-sauce',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Could not find type of defender.', json_decode($response->content())->message);
    }

    public function testWhenNotLoggedInCannotAccessBattleResults() {

        $response = $this->json('POST', '/api/battle-results/1')
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    protected function setUpCharacter(array $options = []): void {
        $this->user = $this->createUser();

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $this->character = (new CharacterSetup)->setupCharacter($this->user, $options)
                                               ->giveItem($item)
                                               ->equipLeftHand()
                                               ->setSkill('Looting', $options)
                                               ->getCharacter();
    }

    protected function setUpMonster(): void {
        $this->monster = $this->createMonster();

        $this->createSkill([
            'monster_id' => $this->monster->id,
        ]);

        $this->createSkill([
            'monster_id' => $this->monster->id,
        ]);
    }
}
