import React, {Fragment} from "react";
import QuestsProps from "../../../lib/game/types/map/quests/quests-props";
import QuestState from "../../../lib/game/types/map/quests/quest-state";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import QuestTree from "./components/quest-tree";
import DropDown from "../../../components/ui/drop-down/drop-down";
import {isEqual} from "lodash";


export default class Quests extends React.Component<QuestsProps, QuestState> {

    constructor(props: any) {
        super(props);

        this.state = {
            quests: [],
            completed_quests: this.props.quest_details.completed_quests,
            current_plane: this.props.quest_details.player_plane,
            loading: false,
        }
    }

    componentDidMount() {
        this.setState({
           quests: this.props.quest_details.quests,
        });
    }

    componentDidUpdate(prevProps: Readonly<QuestsProps>, prevState: Readonly<QuestState>, snapshot?: any) {
        if (!isEqual(this.props.quest_details.completed_quests, this.state.completed_quests)) {
            this.setState({
                completed_quests: this.props.quest_details.completed_quests,
            });
        }
    }

    setPlaneForQuests(plane: string) {
        this.setState({
            current_plane: plane
        });
    }

    render() {

        if (this.state.quests.length === 0) {
            return <ComponentLoading />
        }

        return (
            <Fragment>
                {
                    this.state.loading ?
                        <div className={'h-24 mt-10 relative'}>
                            <ComponentLoading />
                        </div>
                    :
                        <Fragment>
                            <div className='flex items-center'>
                                <div>
                                    <DropDown menu_items={[
                                        {
                                            name: 'Surface',
                                            on_click: this.setPlaneForQuests.bind(this),
                                            icon_class: 'ra ra-footprint'
                                        },
                                        {
                                            name: 'Labyrinth',
                                            on_click: this.setPlaneForQuests.bind(this),
                                            icon_class: 'ra ra-footprint'
                                        },
                                        {
                                            name: 'Dungeons',
                                            on_click: this.setPlaneForQuests.bind(this),
                                            icon_class: 'ra ra-footprint'
                                        },
                                        {
                                            name: 'Shadow Plane',
                                            on_click: this.setPlaneForQuests.bind(this),
                                            icon_class: 'ra ra-footprint'
                                        },
                                        {
                                            name: 'Hell',
                                            on_click: this.setPlaneForQuests.bind(this),
                                            icon_class: 'ra ra-footprint'
                                        },
                                    ]} button_title={'Planes'} />
                                </div>
                                <div>
                                    <a href='/information/quests' target='_blank' className='ml-2'>Quests help <i
                                        className="fas fa-external-link-alt"></i></a>
                                </div>

                            </div>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div className='overflow-x-auto max-w-[400px] sm:max-w-[600px] md:max-w-[100%]'>
                                <QuestTree quests={this.state.quests} completed_quests={this.state.completed_quests} character_id={this.props.character_id} plane={this.state.current_plane} update_quests={this.props.update_quests}/>
                            </div>
                        </Fragment>
                }
            </Fragment>
        );
    }
}
