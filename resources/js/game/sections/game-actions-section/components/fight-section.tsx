import React, {Fragment} from "react";
import AttackButton from "../../../components/ui/buttons/attack-button";
import BattleSetUp from "../../../lib/game/actions/battle/battle-setup";
import {BattleMessage} from "../../../lib/game/actions/battle/types/battle-message-type";
import clsx from "clsx";
import HealthMeters from "./health-meters";
import FightSectionProps from "../../../lib/game/types/actions/fight-section-props";
import Attack from '../../../lib/game/actions/battle/attack/attack/attack';
import AmbushHandler
    from "../../../lib/game/actions/battle/attack/attack/attack-types/ambush-and-counter/AmbushHandler";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import FightSectionState from "../../../lib/game/types/actions/fight-section-state";

export default class FightSection extends React.Component<FightSectionProps, FightSectionState> {

    private battle_messages: BattleMessage[];

    constructor(props: FightSectionProps) {
        super(props);

        this.state = {
            battle_messages: [],
            character_current_health: 0,
            character_max_health: 0,
            monster_current_health: 0,
            monster_max_health: 0,
            monster_to_fight_id: 0,
            is_character_voided: false,
            is_monster_voided: false,
            monster_to_fight: null,
        }

        this.battle_messages = [];
    }

    componentDidMount() {
        this.setUpBattle();
    }

    componentDidUpdate() {
        if (this.props.monster_to_fight.id !== this.state.monster_to_fight_id) {
            this.setState({
                battle_messages: [],
            });

            this.setUpBattle();
        }

        if (this.props.is_same_monster) {
            this.setState({
                battle_messages: [],
            });

            this.setUpBattle();

            this.props.reset_same_monster();
        }

        if (this.props.character_revived) {
            this.setState({
                character_current_health: this.props.character?.health,
                character_max_health: this.props.character?.health,
                battle_messages: [],
            }, () => {
                this.props.reset_revived();
            });

            this.battle_messages = [];
        }
    }

    setUpBattle() {

        if (this.props.character == null) {
            return;
        }

        const battleSetUp = new BattleSetUp(this.props.character, this.props.monster_to_fight);

        battleSetUp.setUp();

        const monsterHealth = battleSetUp.getMonsterHealth();

        this.battle_messages = battleSetUp.getMessages();

        const ambush        = new AmbushHandler();

        const healthObject = ambush.handleAmbush(this.props.character, battleSetUp.getMonsterObject(), this.props.character.health, monsterHealth, battleSetUp.getVoidanceResult().is_character_voided);

        this.battle_messages = [...this.battle_messages, ...ambush.getMessages()];

        if (healthObject.monster_health <= 0 || healthObject.character_health <= 0) {
            this.setState({
                monster_current_health: healthObject.monster_health <= 0 ? 0 : healthObject.monster_health,
                monster_max_health: monsterHealth,
                character_current_health: healthObject.character_health <= 0 ? 0 : parseInt(healthObject.character_health.toFixed(0)),
                character_max_health: parseInt(this.props.character.health.toFixed(0)),
                monster_to_fight_id: this.props.monster_to_fight.id,
                battle_messages: this.battle_messages,
                monster_to_fight: battleSetUp.getMonster(),
            }, () => {
                this.postBattleResults(healthObject.monster_health, healthObject.character_health);
            });
        } else {
            this.setState({
                monster_current_health: healthObject.monster_health,
                monster_max_health: monsterHealth,
                character_current_health: parseInt(healthObject.character_health.toFixed(0)),
                character_max_health: parseInt(this.props.character.health.toFixed(0)),
                monster_to_fight_id: this.props.monster_to_fight.id,
                is_character_voided: battleSetUp.getVoidanceResult().is_character_voided,
                is_monster_voided: battleSetUp.getVoidanceResult().is_monster_voided,
                battle_messages: this.battle_messages,
                monster_to_fight: battleSetUp.getMonster(),
            });
        }
    }

    typeCheck(battleType: 'regular' | 'player-action' | 'enemy-action', type: 'regular' | 'player-action' | 'enemy-action'): boolean {
        return battleType === type;
    }

    renderBattleMessages() {
        return this.state.battle_messages.filter((value: BattleMessage, index: number, self: BattleMessage[]) => {
            return index === self.findIndex((t: BattleMessage) => {
                return t.message === value.message && t.type === value.type;
            });
        }).map((battleMessage: BattleMessage) => {
            return <p className={clsx(
                {
                    'text-green-700 dark:text-green-400': this.typeCheck(battleMessage.type, 'player-action')
                }, {
                    'text-red-500 dark:text-red-400': this.typeCheck(battleMessage.type, 'enemy-action')
                }, {
                    'text-blue-500 dark:text-blue-400': this.typeCheck(battleMessage.type, 'regular')
                }
            )}>
                {battleMessage.message}
            </p>
        });
    }

    attack(attackType: string) {
        const attack = new Attack(this.state.character_current_health, this.state.monster_current_health, this.state.is_character_voided, this.state.is_monster_voided);

        if (this.state.is_character_voided) {
            attackType = 'voided_' + attackType;
        }

        attack.attack(this.props.character, this.state.monster_to_fight, true, 'player', attackType);

        const attackState = attack.getState();

        let characterHealth = attackState.characterCurrentHealth;
        let monsterHealth   = attackState.monsterCurrentHealth;

        if (typeof this.state.character_max_health !== 'undefined') {
            if (characterHealth > this.state.character_max_health) {
                characterHealth = this.state.character_max_health;
            }
        }

        if (monsterHealth > this.state.monster_max_health) {
            monsterHealth = this.state.monster_max_health;
        }

        this.setState({
            battle_messages: [...this.battle_messages, ...attackState.battle_messages],
            monster_current_health: monsterHealth,
            character_current_health: characterHealth,
        });

        this.battle_messages = [];

        if (attackState.characterCurrentHealth <= 0 || attackState.monsterCurrentHealth <= 0) {
            this.postBattleResults(attackState.monsterCurrentHealth, attackState.characterCurrentHealth);
        }
    }

    postBattleResults(monsterHealth: number, characterHealth: number) {
        (new Ajax()).setRoute('battle-results/' + this.props.character?.id).setParameters({
            is_character_dead: characterHealth <= 0,
            is_defender_dead: monsterHealth <= 0,
            monster_id: this.state.monster_to_fight_id,
        }).doAjaxCall('post', (result: AxiosResponse) => {},
            (error: AxiosError) => {
                console.error(error);
            });
    }

    attackButtonDisabled() {
        if (typeof this.state.character_current_health === 'undefined') {
            return true;
        }

        return this.state.monster_current_health <= 0 || this.state.character_current_health <= 0 || this.props.character?.is_dead || !this.props.character?.can_attack
    }

    clearBattleMessages() {
        this.setState({
            battle_messages: [],
            monster_max_health: 0,
        })
    }

    render() {
        return (
            <div className={clsx({'ml-[-125px]': !this.props.is_small})}>
                <div className={clsx('mt-4 mb-4 text-xs text-center', {
                    'hidden': this.attackButtonDisabled()
                })}>
                    <AttackButton additional_css={'btn-attack'} icon_class={'ra ra-sword'} on_click={() => this.attack('attack')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-cast'} icon_class={'ra ra-burning-book'} on_click={() => this.attack('cast')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-cast-attack'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('cast_and_attack')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-attack-cast'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('attack_and_cast')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-defend'} icon_class={'ra ra-round-shield'} on_click={() => this.attack('defend')} disabled={this.attackButtonDisabled()}/>
                    <a href='/information/combat' target='_blank' className='ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a>
                </div>
                {
                    this.attackButtonDisabled() ?
                        <div className='text-center mt-4'>
                            <button onClick={this.clearBattleMessages.bind(this)}
                                    className='text-red-500 dark:text-red-400 underline hover:text-red-600 dark:hover:text-red-500'>
                                Clear
                            </button>
                        </div>
                    : null
                }
                {
                    this.state.monster_max_health > 0 && this.props.character !== null ?
                        <div className={clsx('mb-4 max-w-md m-auto', {
                            'mt-4': this.attackButtonDisabled()
                        })}>
                            <HealthMeters is_enemy={true} name={this.props.monster_to_fight.name} current_health={this.state.monster_current_health} max_health={this.state.monster_max_health} />
                            <HealthMeters is_enemy={false} name={this.props.character.name} current_health={this.state.character_current_health} max_health={this.state.character_max_health} />
                        </div>
                    : null
                }
                <div className='italic text-center'>
                    {this.renderBattleMessages()}
                </div>
            </div>
        )
    }

}
