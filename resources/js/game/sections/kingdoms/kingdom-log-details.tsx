import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import clsx from "clsx";
import { formatNumber } from "../../lib/game/format-number";
import KingdomLogProps from "../../lib/game/kingdoms/types/kingdom-log-props";
import {BuildingLogDetails, UnitLogDetails} from "../../lib/game/kingdoms/kingdom-log-details";

export default class KingdomLogDetails extends React.Component<KingdomLogProps, {  }> {

    constructor(props: KingdomLogProps) {
        super(props);
    }

    renderBuildingChanges() {
        const changes: any = [];

        this.props.log.old_buildings.forEach((oldBuilding: BuildingLogDetails) => {
           let foundNewBuilding: BuildingLogDetails[]|[] = this.props.log.new_buildings.filter((newBuilding: { name: string; durability: number; }) => newBuilding.name === oldBuilding.name);

           if (foundNewBuilding.length > 0) {
               const newBuilding: BuildingLogDetails = foundNewBuilding[0];

               if (newBuilding.durability === oldBuilding.durability) {
                   changes.push(
                       <Fragment>
                           <dt>{oldBuilding.name}</dt>
                           <dd>0% Lost{this.props.log.is_mine ? ', New Durability: ' + formatNumber(newBuilding.durability) : null}</dd>
                       </Fragment>
                   );
               } else if (newBuilding.durability === 0) {
                   changes.push(
                       <Fragment>
                           <dt>{oldBuilding.name}</dt>
                           <dd className='text-red-600 dark:text-red-400'>
                               100% Lost{this.props.log.is_mine ? ', New Durability: ' + formatNumber(newBuilding.durability) : null}
                           </dd>
                      </Fragment>
                   );
               } else {
                   changes.push(
                       <Fragment>
                           <dt>{oldBuilding.name}</dt>
                           <dd className='text-red-600 dark:text-red-400'>
                               {(((oldBuilding.durability - newBuilding.durability) / oldBuilding.durability) * 100).toFixed(0)}% Lost{
                               this.props.log.is_mine ? ', New Durability: ' + formatNumber(newBuilding.durability) : null
                           }
                           </dd>
                       </Fragment>
                   );
               }
           }
        });

        return changes;
    }

    renderUnitChanges() {
        const changes: any = [];

        this.props.log.old_units.forEach((oldUnit: UnitLogDetails) => {
            let foundNewUnit: UnitLogDetails[]|[] = this.props.log.new_units.filter((newUnit: { name: string; amount: number; }) => newUnit.name === oldUnit.name);

            if (foundNewUnit.length > 0) {
                const newUnit: UnitLogDetails = foundNewUnit[0];

                if (newUnit.amount === oldUnit.amount) {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd>0%
                                Lost{this.props.log.is_mine ? ', Amount Left: ' + formatNumber(newUnit.amount) : null}</dd>
                        </Fragment>
                    );
                } else if (newUnit.amount === 0) {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd className='text-red-600 dark:text-red-400'>
                                100% Lost{this.props.log.is_mine ? ', Amount Left: ' + formatNumber(newUnit.amount) : null}
                            </dd>
                        </Fragment>
                    );
                } else {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd className='text-red-600 dark:text-red-400'>
                                {(((oldUnit.amount - newUnit.amount) / oldUnit.amount) * 100).toFixed(2)}% Lost{this.props.log.is_mine ? ', Amount Left: ' + formatNumber(newUnit.amount) : null}
                            </dd>
                        </Fragment>
                    );
                }
            }
        });

        return changes;
    }

    renderUnitsSentChange() {
        const changes: any = [];

        this.props.log.units_sent.forEach((sentUnit: UnitLogDetails) => {
            let foundNewUnit: UnitLogDetails[]|[] = this.props.log.units_survived.filter((newUnit: { name: string; amount: number; }) => newUnit.name === sentUnit.name);

            if (foundNewUnit.length > 0) {
                const newUnit: UnitLogDetails = foundNewUnit[0];

                if (newUnit.amount === sentUnit.amount) {
                    changes.push(
                        <Fragment>
                            <dt>{sentUnit.name}</dt>
                            <dd>0%
                                Lost{!this.props.log.is_mine ? ', Amount Left: ' + formatNumber(newUnit.amount) : null}</dd>
                        </Fragment>
                    );
                } else if (newUnit.amount === 0) {
                    changes.push(
                        <Fragment>
                            <dt>{sentUnit.name}</dt>
                            <dd className='text-red-600 dark:text-red-400'>
                                100% Lost{!this.props.log.is_mine ? ', Amount Left: ' + formatNumber(newUnit.amount) : null}
                            </dd>
                        </Fragment>
                    );
                } else {
                    changes.push(
                        <Fragment>
                            <dt>{sentUnit.name}</dt>
                            <dd className='text-red-600 dark:text-red-400'>
                                {(((sentUnit.amount - newUnit.amount) / sentUnit.amount) * 100).toFixed(0)}% Lost{!this.props.log.is_mine ? ', Amount Left: ' + formatNumber(newUnit.amount) : null}
                            </dd>
                        </Fragment>
                    );
                }
            }
        });

        return changes;
    }

    shouldShowUnitSentChanges(): boolean {
        return this.props.log.units_sent.length > 0 && this.props.log.units_survived.length > 0;
    }

    render() {
        return (
            <BasicCard>
                <div className='text-right cursor-pointer text-red-500'>
                    <button onClick={this.props.close_details}><i className="fas fa-minus-circle"></i></button>
                </div>
                <div className='my-4'>
                    <h3 className='mb-4'>{this.props.log.status}</h3>

                    <dl>
                        <dt>Kingdom Attacked (X/Y)</dt>
                        <dd className={
                            clsx({
                                'text-green-600 dark:text-green-400': !this.props.log.is_mine,
                                'text-red-600 dark:text-red-400': this.props.log.is_mine
                            })
                        }>
                            {this.props.log.to_kingdom_name} {this.props.log.to_x} / {this.props.log.to_y}
                        </dd>
                        <dt>Attacked From (X/Y)</dt>
                        <dd className={
                             clsx({
                                 'text-green-600 dark:text-green-400': this.props.log.is_mine,
                                 'text-red-600 dark:text-red-400': !this.props.log.is_mine
                             })
                         }>
                            {
                                this.props.log.from_kingdom_name !== null ?
                                    this.props.log.from_kingdom_name + ' ' + this.props.log.from_x + '/' + this.props.log.from_y
                                :
                                    'N/A'
                            }
                        </dd>
                        <dt className={this.props.log.took_kingdom ? 'hidden' : ''}>Kingdom Attacked Morale Loss</dt>
                        <dd className={'text-red-600 dark:text-red-400 ' + this.props.log.took_kingdom ? 'hidden' : ''}>{(this.props.log.morale_loss * 100).toFixed(2)} %</dd>
                    </dl>

                    <div className={'border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 ' + (!this.props.log.took_kingdom ? 'hidden' : '')}></div>

                    <p className={!this.props.log.took_kingdom ? 'hidden' : ''}>
                        You now own this kingdom. You took it from the defender. Check your kingdoms list.
                        Any surviving units are now held up here.
                    </p>
                </div>
                <div className={'border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 ' + this.props.log.took_kingdom ? 'hidden' : ''}></div>
                <div className={this.props.log.took_kingdom ? 'hidden' : ''}>
                    <div className={'grid md:grid-cols-'+ (this.shouldShowUnitSentChanges() ? '3' : '2') +' gap-2'}>
                        <div>
                            <h3 className='mb-4'>
                                Building Changes
                            </h3>
                            <dl>
                                {this.renderBuildingChanges()}
                            </dl>
                        </div>
                        {
                            this.props.log.old_units.length === 0 && this.props.log.new_units.length === 0 ?
                                <div>
                                    <h3 className='mb-4'>
                                        Unit Changes
                                    </h3>
                                    <p>There were no changes in kingdom units.</p>
                                </div>
                            :
                                <div>
                                    <h3 className='mb-4'>
                                        Unit Changes
                                    </h3>
                                    <dl>
                                        {this.renderUnitChanges()}
                                    </dl>
                                </div>
                        }

                        {
                            this.shouldShowUnitSentChanges() ?
                                <div>
                                    <h3 className='mb-4'>
                                        Attacking Unit Changes
                                    </h3>
                                    <dl>
                                        {this.renderUnitsSentChange()}
                                    </dl>
                                </div>
                            : null
                        }
                    </div>
                </div>
            </BasicCard>
        )
    }
}
