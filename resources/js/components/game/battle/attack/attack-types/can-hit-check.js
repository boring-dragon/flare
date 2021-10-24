import Damage from "../damage";
import {random} from "lodash";

const battleMessages = [];

export default class CanHitCheck {

  constructor() {
    this.battleMessages = [];
    this.canAutoHit     = false;
  }

  canHit (attacker, defender, battleMessages, voided) {
    const damage        = new Damage();

    if (attacker.hasOwnProperty('class')) {
      if (damage.canAutoHit(attacker)) {
        this.battleMessages = [...this.battleMessages, ...damage.getMessages()];

        this.canAutoHit = true;

        return true;
      }
    }

    let attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus;
    let defenderDodge    = defender.dodge
    let toHitBase        = this.toHitCalculation(attacker.to_hit_base, defender.dex, attackerAccuracy, defenderDodge);

    if (attackerAccuracy > 1.0) {
      return true;
    }

    if (defenderDodge > 1.0) {
      return false;
    }

    return this.calculateCanHit(toHitBase);
  }

  canCast(attacker, defender) {
    const damage         = new Damage();
    let attackerAccuracy = null;
    let dodge            = null;

    if (attacker.hasOwnProperty('class')) {
      if (damage.canAutoHit(attacker)) {
        this.battleMessages = [...battleMessages, ...damage.getMessages()];

        return true;
      }

      attackerAccuracy = attacker.skills.filter(s => s.name === 'Casting Accuracy')[0].skill_bonus;
      dodge            = defender.dodge;
    } else {
      attackerAccuracy = attacker.casting_accuracy;
      dodge            = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;
    }

    if (attackerAccuracy > 1.0) {
      return true;
    }

    if (dodge > 1.0) {
      return false;
    }

    let toHitBase = this.toHitCalculation(attacker.to_hit_base, defender.focus, attackerAccuracy, dodge);

    return this.calculateCanHit(toHitBase);
  }

  canMonsterHit(attacker, defender) {
    let monsterAccuracy = attacker.accuracy;
    let defenderDodge   = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;
    let toHitBase       = this.toHitCalculation(attacker.to_hit_base, attacker.dex, monsterAccuracy, defenderDodge);

    if (monsterAccuracy > 1.0) {
      return true;
    }

    if (defenderDodge > 1.0) {
      return false;
    }

    return this.calculateCanHit(toHitBase);
  }

  calculateCanHit(toHitBase) {
    if (Math.sign(toHitBase) === - 1) {
      toHitBase = Math.abs(toHitBase);
    }

    if (toHitBase > 1.0) {
      return true;
    }

    const percentage = Math.floor((100 - toHitBase));

    const needToHit = 100 - percentage;

    return (Math.random() * (100 - 1) + 1) > needToHit;
  }

  getBattleMessages () {
    return this.battleMessages;
  }

  getCanAutoHit() {
    return this.canAutoHit;
  }

  toHitCalculation(toHit, dex, accuracy, dodge) {
    const enemyDex = (dex / 10000);
    const hitChance = ((toHit + toHit * accuracy) / 100);

    return (enemyDex + enemyDex * dodge) - hitChance;
  }
}

