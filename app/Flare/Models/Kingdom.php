<?php

namespace App\Flare\Models;

use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\KingdomFactory;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Kingdom extends Model implements Auditable
{

    use AuditableTrait;

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_map_id',
        'name',
        'color',
        'max_stone',
        'max_wood',
        'max_clay',
        'max_iron',
        'current_stone',
        'current_wood',
        'current_clay',
        'current_iron',
        'current_population',
        'max_population',
        'x_position',
        'y_position',
        'current_morale',
        'max_morale',
        'treasury',
        'published',
        'npc_owned',
        'last_walked',
        'updated_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'color'              => 'array',
        'max_stone'          => 'integer',
        'max_wood'           => 'integer',
        'max_clay'           => 'integer',
        'max_iron'           => 'integer',
        'current_stone'      => 'integer',
        'current_wood'       => 'integer',
        'current_clay'       => 'integer',
        'current_iron'       => 'integer',
        'current_population' => 'integer',
        'max_population'     => 'integer',
        'x_position'         => 'integer',
        'y_position'         => 'integer',
        'current_morale'     => 'float',
        'max_morale'         => 'float',
        'treasury'           => 'integer',
        'published'          => 'boolean',
        'npc_owned'          => 'boolean',
        'last_walked'        => 'datetime',
    ];

    /**
     * Update the last walked automatically.
     */
    public function updateLastWalked() {
        $this->update([
            'last_walked' => now(),
        ]);
    }

    public function fetchDefenceBonusFromPassive(): float {
        return $this->getPercentage(PassiveSkillTypeValue::KINGDOM_DEFENCE);
    }

    public function fetchResourceBonus(): float {
        return $this->getPercentage(PassiveSkillTypeValue::KINGDOM_RESOURCE_GAIN);
    }

    public function fetchUnitCostReduction(): float {
        return $this->getPercentage(PassiveSkillTypeValue::KINGDOM_UNIT_COST_REDUCTION);
    }

    public function fetchBuildingCostReduction(): float {
        return $this->getPercentage(PassiveSkillTypeValue::KINGDOM_BUILDING_COST_REDUCTION);
    }

    public function fetchIronCostReduction(): float {
        return $this->getPercentage(PassiveSkillTypeValue::IRON_COST_REDUCTION);
    }

    public function fetchPopulationCostReduction(): float {
        return $this->getPercentage(PassiveSkillTypeValue::POPULATION_COST_REDUCTION);
    }

    public function fetchKingdomDefenceBonus(): float {
        $passiveBonus = $this->fetchDefenceBonusFromPassive();
        $treasury     = $this->fetchTreasuryDefenceBonus();
        $walls        = $this->getWallsDefence();
        $goldBars     = $this->fetchGoldBarsDefenceBonus();

        return $walls + $treasury + $goldBars + $passiveBonus;
    }

    public function fetchTreasuryDefenceBonus(): float {
        return $this->treasury / KingdomMaxValue::MAX_TREASURY;
    }

    public function fetchGoldBarsDefenceBonus(): float {
        return $this->gold_bars / 1000;
    }

    public function getWallsDefence(): float {
        $walls = $this->buildings->filter(function($building) {
            return $building->gameBuilding->is_walls;
        })->first();

        if ($walls->current_durability <= 0) {
            return 0.0;
        }

        return $walls->level / 30;
    }

    public function gameMap() {
        return $this->belongsTo(GameMap::class, 'game_map_id', 'id');
    }

    public function buildings() {
        return $this->hasMany(KingdomBuilding::class, 'kingdom_id', 'id');
    }

    public function buildingsQueue() {
        return $this->hasMany(BuildingInQueue::class, 'kingdom_id', 'id');
    }

    public function unitsQueue() {
        return $this->hasMany(UnitInQueue::class, 'kingdom_id', 'id');
    }

    public function unitsMovementQueue() {
        return $this->hasMany(UnitMovementQueue::class, 'from_kingdom_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function units() {
        return $this->hasMany(KingdomUnit::class, 'kingdom_id', 'id');
    }

    protected static function newFactory() {
        return KingdomFactory::new();
    }

    protected function getPercentage(int $passiveType): float {
        $character    = $this->character;

        if (is_null($character)) {
             return 0.0;
        }

        $passive = $character->passiveSkills->filter(function($passiveSkill) use($passiveType) {
            return $passiveSkill->passiveSkill->effect_type === $passiveType;
        })->first();

        return $passive->current_level * $passive->passiveSkill->bonus_per_level;
    }
}
