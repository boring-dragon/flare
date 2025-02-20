import React, {Fragment} from "react";
import {formatNumber} from "../../../../lib/game/format-number";
import SpecialLocationHelpModal from "./special-location-help-modal";
import LocationDetailsProps from "../../../../lib/game/types/map/location-pins/modals/location-details-props";

export default class LocationDetails extends React.Component<LocationDetailsProps, any> {
    constructor(props: LocationDetailsProps) {
        super(props)

        this.state = {
            open_help_dialogue: false,
        }
    }

    manageHelpDialogue() {
        this.setState({
            open_help_dialogue: !this.state.open_help_dialogue
        })
    }

    isSpecialLocation(): boolean {
        return this.props.location.increase_enemy_percentage_by !== null;
    }

    renderSpecialType() {
        if (this.props.location.type_name === 'Gold Mines') {
            return (
                <Fragment>
                    <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                    <h5 className='text-orange-500 dark:text-orange-400'>Gold Mines!</h5>
                    <p className='my-4'>
                        You are in the Gold Mines! This place will drops between 1-25 Shards per kill. You <strong>can explore here</strong>.
                        This is the only place known to drop <a href='/information/currencies' target="_blank">Crystal Shards <i className="fas fa-external-link-alt"></i></a>,
                        aside from Celestials who drop the most.
                    </p>
                </Fragment>
            )
        }

        if (this.props.location.type_name === 'Purgatory Dungeons') {
            return (
                <Fragment>
                    <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                    <h5 className='text-orange-500 dark:text-orange-400'>Purgatory Dungeons!</h5>
                    <p className='my-4'>
                        You have entered into the Purgatory Dungeons. You <strong>can explore here</strong>.
                        This is the only place known to drop <a href='/information/gear-progression' target="_blank">Mythic Items <i className="fas fa-external-link-alt"></i></a>,
                        aside from Celestial Kings who spawn after <a href='/information/monthly-pvp-event' target="_blank">Monthly PVP <i className="fas fa-external-link-alt"></i></a>
                    </p>
                </Fragment>
            )
        }

        return null;
    }

    render() {
        console.log(this.props.location);
        return (
            <Fragment>
                <p className='my-3'>{this.props.location.description}</p>
                {
                    this.isSpecialLocation() ?
                        <Fragment>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div className='flex items-center mb-4'>
                                <h4>Special Location Details</h4>
                                <div>
                                    <button type={"button"} onClick={this.manageHelpDialogue.bind(this)} className='text-blue-500 dark:text-blue-300 ml-2'>
                                        <i className={'fas fa-info-circle'}></i> Help
                                    </button>
                                </div>
                            </div>
                            <p className={'mb-4'}>
                                Places like this can increase the enemies stats and resistances as well as skills. It is essential that players craft appropriate resistance
                                and stat reduction gear to survive harder creatures here.
                            </p>
                            <dl className={'mb-4'}>
                                <dt>Increase Core Stats By: </dt>
                                <dd>{formatNumber(this.props.location.increases_enemy_stats_by)}</dd>
                                <dt>Increase Percentage Based Values By: </dt>
                                <dd>{this.props.location.increase_enemy_percentage_by !== null ?
                                        (this.props.location.increase_enemy_percentage_by * 100).toFixed(0)
                                    : 0
                                }%</dd>
                            </dl>

                            {
                                this.props.location.type_name !== null ?
                                    this.renderSpecialType()
                                : null
                            }
                        </Fragment>
                    :
                        this.props.location.quest_reward_item_id !== null ?
                            <Fragment>
                                <dl className='mb-4'>
                                    <dt>Quest Item (Gained on visiting)</dt>
                                    <dd>
                                        <a href={"/information/item/" + this.props.location.quest_reward_item_id} target='_blank'>
                                            {this.props.location.quest_reward_item.affix_name} <i
                                            className="fas fa-external-link-alt"></i>
                                        </a>
                                    </dd>
                                </dl>
                            </Fragment>
                        : null
                }

                {
                    this.state.open_help_dialogue ?
                        <SpecialLocationHelpModal manage_modal={this.manageHelpDialogue.bind(this)} />
                    : null
                }
            </Fragment>
        )
    }
}
