<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\InfoPage;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Traits\Controllers\MonstersShowInformation;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Traits\Controllers\ItemsShowInformation;
use App\Game\Core\Values\View\ClassBonusInformation;

class InfoPageController extends Controller
{

    use ItemsShowInformation, MonstersShowInformation;

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function viewPage(Request $request, string $pageName) {
        $page = InfoPage::where('page_name', $pageName)->first();

        if (is_null($page)) {
            abort(404);
        }

        $sections = $page->page_sections;

        array_multisort(array_column($sections, 'order'), SORT_ASC, $sections);

        return view('information.core', [
            'pageTitle' => ucfirst(str_replace('-', ' ', $page->page_name)),
            'sections'  => $sections,
            'pageId'    => $page->id,
        ]);
    }

    public function viewRace(Request $request, GameRace $race) {
        return view('information.races.race', [
            'race' => $race,
        ]);
    }

    public function viewClass(Request $request, GameClass $class) {
        return view('information.classes.class', [
            'class' => $class,
            'classBonus' => (new ClassBonusInformation())->buildClassBonusDetailsForInfo($class->name),
        ]);
    }

    public function viewMap(GameMap $map) {

        $effects = match ($map->name) {
            'Labyrinth'    => ItemEffectsValue::LABYRINTH,
            'Dungeons'     => ItemEffectsValue::DUNGEON,
            'Shadow Plane' => ItemEffectsValue::SHADOWPLANE,
            'Hell'         => ItemEffectsValue::HELL,
            'Purgatory'    => ItemEffectsValue::PURGATORY,
            default        => '',
        };

        $walkOnWater = match ($map->name) {
            'Labyrinth', 'Surface' => ItemEffectsValue::WALK_ON_WATER,
            'Dungeons'     => ItemEffectsValue::WALK_ON_DEATH_WATER,
            'Hell'         => ItemEffectsValue::WALK_ON_MAGMA,
            default        => '',
        };

        return view('information.maps.map', [
            'map'         => $map,
            'itemNeeded'  => Item::where('effect', $effects)->first(),
            'walkOnWater' => Item::where('effect', $walkOnWater)->first(),
            'mapUrl'      => Storage::disk('maps')->url($map->path),
        ]);
    }

    public function viewSkill(Request $request, GameSkill $skill) {
        return view('information.skills.skill', [
            'skill' => $skill,
        ]);
    }

    public function viewMonsters() {
        return view('information.monsters.monsters', [
            'gameMapNames' => GameMap::all()->pluck('name')->toArray(),
        ]);
    }

    public function viewMonster(Request $request, Monster $monster) {
        return $this->renderMonsterShow($monster, 'information.monsters.monster');
    }

    public function viewLocation(Request $request, Location $location) {
        $increasesEnemyStrengthBy = null;
        $increasesDropChanceBy    = 0.0;
        $locationType             = null;

        if (!is_null($location->enemy_strength_type)) {
            $increasesEnemyStrengthBy = LocationEffectValue::getIncreaseName($location->enemy_strength_type);
            $increasesDropChanceBy    = (new LocationEffectValue($location->enemy_strength_type))->fetchDropRate();
        }

        $questItemDetails = [];

        if (!is_null($location->questRewardItem)) {
            $questItemDetails = $this->itemShowDetails($location->questRewardItem);
        }

        if (!is_null($location->type)) {
            $locationType = (new LocationType($location->type));
        }

        return view('information.locations.location', array_merge([
            'location'                 => $location,
            'increasesEnemyStrengthBy' => $increasesEnemyStrengthBy,
            'increasesDropChanceBy'    => $increasesDropChanceBy,
            'locationType'             => $locationType,
        ], $questItemDetails));
    }

    public function viewUnit(Request $request, GameUnit $unit) {
        $belongsToKingdomBuilding = GameBuildingUnit::where('game_unit_id', $unit->id)->first();

        if (!is_null($belongsToKingdomBuilding)) {
            $belongsToKingdomBuilding = $belongsToKingdomBuilding->gameBuilding;
        }

        return view('information.units.unit', [
            'unit'          => $unit,
            'building'      => $belongsToKingdomBuilding,
            'requiredLevel' => GameBuildingUnit::where('game_building_id', $belongsToKingdomBuilding->id)
                                               ->where('game_unit_id', $unit->id)
                                               ->first()->required_level
        ]);
    }

    public function viewBuilding(GameBuilding $building) {
        return view('information.buildings.building', [
            'building' => $building
        ]);
    }

    public function viewItem(Request $request, Item $item) {
        return $this->renderItemShow('information.items.item', $item);
    }

    public function viewAffix(Request $request, ItemAffix $affix) {
        return view('information.affixes.affix', [
            'itemAffix' => $affix
        ]);
    }

    public function viewNpc(Npc $npc) {
        return view('information.npcs.npc', [
            'npc' => $npc
        ]);
    }

    public function viewQuest(Quest $quest) {
        $skill = null;

        if ($quest->unlocks_skill) {
            $skill = GameSkill::where('type', $quest->unlocks_skill_type)->where('is_locked', true)->first();
        }

        return view('information.quests.quest', [
            'quest'       => $quest,
            'lockedSkill' => $skill,
        ]);
    }

    public function viewPassiveSkill(PassiveSkill $passiveSkill) {
        return view('information.passive-skills.skill', [
            'skill' => $passiveSkill,
        ]);
    }

    protected function cleanFiles(array $files): array {
        $clean = [];

        foreach ($files as $index => $path) {
            if (explode('.', $path)[1] === 'DS_Store') {
                // @codeCoverageIgnoreStart
                unset($files[$index]);  // => We do not need this tested. Test environment would never have a mac specific file.
                // @codeCoverageIgnoreEnd
            } else {
                $clean[] = $path;
            }
        }

        return $clean;
    }
}
