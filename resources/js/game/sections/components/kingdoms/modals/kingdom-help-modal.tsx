import React from "react";
import HelpDialogue from "../../../../components/ui/dialogue/help-dialogue";

export default class KingdomHelpModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    buildTitle() {
        switch (this.props.type) {
            case 'wall_defence':
                return 'Wall Defence'
            case 'treas_defence':
                return 'Treasury Defence'
            case 'gb_defence':
                return 'Gold Bars Defence'
            case 'passive_defence':
                return 'Passive Skill Defence'
            case 'total_defence':
                return 'Total Defence'
            case 'teleport_details':
                return 'Teleport Details'
            case 'total_damage':
                return 'Items Damage (minus kingdom defence reduction)';
            case 'building_damage':
                return 'Damage to the defenders buildings';
            case 'unit_damage':
                return 'Damage to the defenders units';
            case 'total_reduction':
                return 'Defenders Damage Reduction';
            default:
                return 'Error. Unknown type.'
        }
    }

    renderWallDefence() {
        return (
            <p className='my-4'>
                This is calculated by your wall level divided wall max level to give the kingdom additional defence bonus towards attacking
                units and items that are dropped on your kingdoms.
            </p>
        );
    }

    renderTreasuryDefence() {
        return (
            <p className='my-4'>
                This is calculated by dividing the amount of treasure by the max you can store: 2 Billion Gold.
                This defence bonus is then added to walls and the other bonuses below to give the kingdom an over all defence bonus.
            </p>
        );
    }

    renderGoldBarsDefence() {
        return (
            <p className='my-4'>
                This is the amount of gold bars you have divided by the amount you can have which is 1000 at a cost of 2 billion each.
                Gold bars can only be purchased in kingdoms where you have the <a href='/information/kingdom-passive-skills' target='_blank'>
                Goblin Bank <i className="fas fa-external-link-alt"></i></a> building unlocked. This is then added to other defence bonuses you have.
            </p>
        );
    }

    renderPassiveDefence() {
        return (
            <p className='my-4'>
                By training <a href='/information/kingdom-passive-skills' target='_blank'>passive skills <i className="fas fa-external-link-alt"></i></a> Your kingdom can unlock additional defence
                bonus for yur kingdom. This is then applied to all other defence bonuses.
            </p>
        )
    }

    renderTotalDefence() {
        return (
            <p className='my-4'>
                This is the total and combined defence your kingdom has. If another player sends cannons towards you, your defence will be capped at 45% regardless if
                it is higher then that. Defence bonus mostly protects against users using items on your kingdom that can do up to 100% in damage for a single one.
            </p>
        )
    }

    renderTeleportDetails() {
        return (
            <p className='my-4'>
                This location will let you teleport to it for a fee and a timeout in minutes. If you have trained the skill <a href='/information/skill-information' target='_blank'>
                Quick Feet <i className="fas fa-external-link-alt"></i></a> to a high enough level then the timer will reduce the time before you can move again by a % down to a maximum of 1 minute.
                If the teleport button is disabled, you cannot afford to travel.
            </p>
        );
    }

    renderTotalDamage() {
        return (
            <p className='my-4'>
                This is the total damage of the the items you have selected. The damage is reduced based on the
                defenders kingdom defence reduction. Maximum reduction is 45%.
            </p>
        );
    }

    renderBuildingDamage() {
        return (
            <p className='my-4'>
                Based on the items you have selected, 50% of the damage is used against the buildings of the kingdom.
            </p>
        );
    }

    renderUnitDamage() {
        return (
            <p className='my-4'>
                Based on the items you have selected, 50% of the damage is used against the units of the kingdom.
            </p>
        );
    }

    renderTotalReduction() {
        return (
            <p className='my-4'>
                If the kingdom has over 100% total defence, then we divide the amount over 100% by 5%. giving a maximum of 20%
                reduction in damage. this means a kingdom with 325% reduces the damage by 45%. However, if the enemy kingdom
                has 101% total defence, we will reduce the damage by 5%.
            </p>
        );
    }

    buildSections() {
        switch (this.props.type) {
            case 'wall_defence':
                return this.renderWallDefence();
            case 'treas_defence':
                return this.renderTreasuryDefence();
            case 'gb_defence':
                return this.renderGoldBarsDefence();
            case 'passive_defence':
                return this.renderPassiveDefence();
            case 'total_defence':
                return this.renderTotalDefence();
            case 'teleport_details':
                return this.renderTeleportDetails();
            case 'total_damage':
                return this.renderTotalDamage();
            case 'building_damage':
                return this.renderBuildingDamage();
            case 'unit_damage':
                return this.renderUnitDamage();
            case 'total_reduction':
                return this.renderTotalReduction();
            default:
                return 'Error. Unknown type.'
        }
    }

    render() {
        return (
            <HelpDialogue is_open={true}
                          manage_modal={this.props.manage_modal}
                          title={this.buildTitle()}>
                {this.buildSections()}
            </HelpDialogue>
        );
    }
}
