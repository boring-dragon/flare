<?php

namespace App\Flare\ServerFight;

use App\Flare\ServerFight\Fight\CharacterAttacks\Counter;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class BattleBase extends BattleMessages {

    protected int $characterHealth;

    protected int $monsterHealth;

    protected array $attackData;

    protected bool $isVoided = false;

    protected bool $isEnemyVoided = false;

    protected bool $isEnemyEntranced = false;

    protected bool $allowSecondaryAttacks = true;

    protected bool $abortCharacterIsDead = false;

    protected CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function setCharacterHealth(int $characterHealth) {
        $this->characterHealth = $characterHealth;
    }

    public function setMonsterHealth(int $monsterHealth) {
        $this->monsterHealth = $monsterHealth;
    }

    public function getCharacterHealth(): int {
        return $this->characterHealth;
    }

    public function getMonsterHealth(): int {
        return $this->monsterHealth;
    }

    public function doNotAllowSecondaryAttacks() {
        $this->allowSecondaryAttacks = false;
    }

    public function setEntranced() {
        $this->isEnemyEntranced = true;
    }

    public function setIsEnemyVoided(bool $isVoided) {
        $this->isEnemyVoided = $isVoided;
    }

    protected function doPvpEntrance(Character $attacker, Entrance $entrance) {
        $entrance->attackerEntrancesDefender($attacker, $this->attackData, $this->isVoided);

        $this->mergeAttackerMessages($entrance->getAttackerMessages());
        $this->mergeDefenderMessages($entrance->getDefenderMessages());

        if ($entrance->isEnemyEntranced()) {
            $this->isEnemyEntranced = true;
        }
    }

    protected function doEnemyEntrance(Character $character, ServerMonster $monster, Entrance $entrance) {
        $entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($entrance->getMessages());

        if ($entrance->isEnemyEntranced()) {
            $this->isEnemyEntranced = true;
        }
    }

    protected function secondaryAttack(Character $character, ServerMonster $monster = null, float $affixReduction = 0.0, bool $isPvp = false) {
        $secondaryAttacks = resolve(SecondaryAttacks::class);

        $secondaryAttacks->setMonsterHealth($this->monsterHealth);
        $secondaryAttacks->setCharacterHealth($this->characterHealth);
        $secondaryAttacks->setAttackData($this->attackData);
        $secondaryAttacks->setIsCharacterVoided($this->isVoided);
        $secondaryAttacks->setIsEnemyEntranced($this->isEnemyEntranced);

        $secondaryAttacks->doSecondaryAttack($character, $monster, $affixReduction, $isPvp);

        $this->monsterHealth   = $secondaryAttacks->getMonsterHealth();
        $this->characterHealth = $secondaryAttacks->getCharacterHealth();

        if ($isPvp) {
            $this->mergeAttackerMessages($secondaryAttacks->getAttackerMessages());
            $this->mergeDefenderMessages($secondaryAttacks->getDefenderMessages());
        } else {
            $this->mergeMessages($secondaryAttacks->getMessages());
        }

        $secondaryAttacks->clearMessages();
    }

    protected function pvpCounter(Character $attacker, Character $defender) {
        $counter = resolve(Counter::class);

        $counter->setCharacterHealth($this->characterHealth);
        $counter->setMonsterHealth($this->monsterHealth);
        $counter->setIsEnemyVoided($this->isEnemyVoided);
        $counter->setIsAttackerVoided($this->isVoided);
        $counter->pvpCounter($attacker, $defender);

        $this->mergeAttackerMessages($counter->getAttackerMessages());
        $this->mergeDefenderMessages($counter->getDefenderMessages());

        $this->characterHealth = $counter->getCharacterHealth();
        $this->monsterHealth   = $counter->getMonsterHealth();

        $counter->clearMessages();
    }

    protected function doMonsterCounter(Character $character, ServerMonster $monster) {
        $counter = resolve(Counter::class);

        $counter->setCharacterHealth($this->characterHealth);
        $counter->setMonsterHealth($this->monsterHealth);
        $counter->setIsAttackerVoided($this->isVoided);
        $counter->monsterCounter($character, $monster);

        $this->mergeAttackerMessages($counter->getAttackerMessages());
        $this->mergeDefenderMessages($counter->getDefenderMessages());

        $this->characterhealth = $counter->getCharacterHealth();
        $this->monsterHealth   = $counter->getMonsterHealth();

        $counter->clearMessages();
    }

    protected function doPlayerCounterMonster(Character $character, ServerMonster $monster) {
        $counter = resolve(Counter::class);

        $counter->setCharacterHealth($this->characterHealth);
        $counter->setMonsterHealth($this->monsterHealth);
        $counter->setIsAttackerVoided($this->isVoided);
        $counter->playerCounter($character, $monster);

        $this->mergeAttackerMessages($counter->getAttackerMessages());
        $this->mergeDefenderMessages($counter->getDefenderMessages());

        $this->characterhealth = $counter->getCharacterHealth();
        $this->monsterHealth   = $counter->getMonsterHealth();

        $counter->clearMessages();
    }

    protected function getPvpCharacterAc(Character $defender) {
        $defence = $this->characterCacheData->getCharacterDefenceAc($defender);

        if (!is_null($defence)) {
            return $defence;
        }

        return $this->characterCacheData->getCachedCharacterData($defender, 'ac');
    }

    protected function canBlock(int $damage, int $ac) {
        return $ac > $damage;
    }
}
