<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\AttackAndCast;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastAndAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\Defend;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Flare\ServerFight\Monster\ServerMonster;

class CharacterAttack {

    private WeaponType $weaponType;

    private CastType $castType;

    private AttackAndCast $attackAndCast;

    private CastAndAttack $castAndAttack;

    private Defend $defend;

    private mixed $type;

    public function __construct(WeaponType $weaponType, CastType $castType, AttackAndCast $attackAndCast, CastAndAttack $castAndAttack, Defend $defend) {
        $this->weaponType    = $weaponType;
        $this->castType      = $castType;
        $this->attackAndCast = $attackAndCast;
        $this->castAndAttack = $castAndAttack;
        $this->defend        = $defend;
    }

    public function pvpAttack(Character $attacker, Character $defender, bool $isAttackerVoided, bool $isEnemyVoided, array $healthObject): CharacterAttack {
        $this->weaponType->setCharacterHealth($healthObject['attacker_health']);
        $this->weaponType->setMonsterHealth($healthObject['defender_health']);
        $this->weaponType->setCharacterAttackData($attacker, $isAttackerVoided);
        $this->weaponType->setIsEnemyVoided($isEnemyVoided);
        $this->weaponType->doPvpWeaponAttack($attacker, $defender);

        $this->type = $this->weaponType;

        return $this;
    }

    public function attack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->weaponType->setCharacterHealth($characterHealth);
        $this->weaponType->setMonsterHealth($monsterHealth);
        $this->weaponType->setCharacterAttackData($character, $isPlayerVoided);
        $this->weaponType->doWeaponAttack($character, $monster);

        $this->type = $this->weaponType;

        return $this;
    }

    public function pvpCast(Character $attacker, Character $defender, bool $isAttackerVoided, bool $isEnemyVoided, array $healthObject): CharacterAttack {
        $this->castType->setCharacterHealth($healthObject['attacker_health']);
        $this->castType->setMonsterHealth($healthObject['defender_health']);
        $this->castType->setCharacterAttackData($attacker, $isAttackerVoided);
        $this->castType->setIsEnemyVoided($isEnemyVoided);
        $this->castType->pvpCastAttack($attacker, $defender);

        $this->type = $this->castType;

        return $this;
    }

    public function cast(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->castType->setCharacterHealth($characterHealth);
        $this->castType->setMonsterHealth($monsterHealth);
        $this->castType->setCharacterAttackData($character, $isPlayerVoided);
        $this->castType->castAttack($character, $monster);

        $this->type = $this->castType;

        return $this;
    }

    public function pvpAttackAndCast(Character $attacker, Character $defender, bool $isAttackerVoided, bool $isEnemyVoided, array $healthObject): CharacterAttack {
        $this->attackAndCast->setCharacterHealth($healthObject['attacker_health']);
        $this->attackAndCast->setMonsterHealth($healthObject['defender_health']);
        $this->attackAndCast->setCharacterAttackData($attacker, $isAttackerVoided);
        $this->attackAndCast->setIsEnemyVoided($isEnemyVoided);
        $this->attackAndCast->handlePvpAttack($attacker, $defender);

        $this->type = $this->attackAndCast;

        return $this;
    }

    public function attackAndCast(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->attackAndCast->setCharacterHealth($characterHealth);
        $this->attackAndCast->setMonsterHealth($monsterHealth);
        $this->attackAndCast->setCharacterAttackData($character, $isPlayerVoided);
        $this->attackAndCast->handleAttack($character, $monster);

        $this->type = $this->attackAndCast;

        return $this;
    }

    public function pvpCastAndAttack(Character $attacker, Character $defender, bool $isAttackerVoided, bool $isEnemyVoided, array $healthObject): CharacterAttack {
        $this->castAndAttack->setCharacterHealth($healthObject['attacker_health']);
        $this->castAndAttack->setMonsterHealth($healthObject['defender_health']);
        $this->castAndAttack->setCharacterAttackData($attacker, $isAttackerVoided);
        $this->castAndAttack->setIsEnemyVoided($isEnemyVoided);
        $this->castAndAttack->handlePvpAttack($attacker, $defender);

        $this->type = $this->castAndAttack;

        return $this;
    }

    public function castAndAttack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->castAndAttack->setCharacterHealth($characterHealth);
        $this->castAndAttack->setMonsterHealth($monsterHealth);
        $this->castAndAttack->setCharacterAttackData($character, $isPlayerVoided);
        $this->castAndAttack->handleAttack($character, $monster);

        $this->type = $this->castAndAttack;

        return $this;
    }

    public function pvpDefend(Character $attacker, Character $defender, bool $isAttackerVoided, bool $isEnemyVoided, array $healthObject): CharacterAttack {
        $this->defend->setCharacterHealth($healthObject['attacker_health']);
        $this->defend->setMonsterHealth($healthObject['defender_health']);
        $this->defend->setCharacterAttackData($attacker, $isAttackerVoided);
        $this->defend->setIsEnemyVoided($isEnemyVoided);
        $this->defend->pvpDefend($attacker, $defender);

        $this->type = $this->defend;

        return $this;
    }

    public function defend(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->defend->setCharacterHealth($characterHealth);
        $this->defend->setMonsterHealth($monsterHealth);
        $this->defend->setCharacterAttackData($character, $isPlayerVoided);
        $this->defend->defend($character, $monster);

        $this->type = $this->defend;

        return $this;
    }

    public function getMessages() {
        return $this->type->getMessages();
    }

    public function getAttackerMessages() {
        return $this->type->getAttackerMessages();
    }

    public function getDefenderMessages() {
        return $this->type->getDefenderMessages();
    }

    public function resetMessages() {
        $this->type->resetMessages();
    }

    public function getCharacterHealth() {
        return $this->type->getCharacterHealth();
    }

    public function getMonsterHealth() {
        return $this->type->getMonsterHealth();
    }
}
