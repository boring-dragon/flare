<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Monster;
use App\Flare\Models\Skill;

class SkillXPCalculator {

    /**
     * Fetches the total skill exp.
     *
     * Applies equipment, quest item, adventure bonuses and percentage of xp towards, to skill exp which starts at a
     * a base of 5.
     *
     * @param Skill $skill
     * @param Monster|null $monster
     * @return float|int
     */
    public function fetchSkillXP(Skill $skill, Monster $monster = null) {
        $xpTowards      = $this->getXpTowards($skill, $monster);
        $totalBonus     = $skill->skill_training_bonus;

        if ($skill->can_train) {
            $base = 5 + $xpTowards;
        } else {
            $base = 25;
        }

        return $base + $base * $totalBonus;
    }

    protected function getXpTowards(Skill $skill, Monster $monster = null) {
        if (is_null($monster)) {
             return 0;
        }

        $totalTowards = 0;

        $monsterXP = $monster->xp;

        if (!is_null($skill->xp_towards)) {
            $totalTowards = (int) number_format($monsterXP - ($monsterXP * $skill->xp_towards));

            if ($totalTowards === 0) {
                $totalTowards = $monster->xp;
            }
        }

        return $totalTowards;
    }
}
