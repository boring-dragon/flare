import React, {Fragment} from "react";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
import KingdomPassiveTree from "./skill-tree/kingdom-passive-tree";
import TimerProgressBar from "../../../../../components/ui/progress-bars/timer-progress-bar";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import {DateTime} from "luxon";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";

export default class KingdomPassives extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            kingdom_passives: [],
            success_message: null,
            skill_in_training: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/kingdom-passives/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                kingdom_passives: result.data.kingdom_passives,
            }, () => {
                this.findSkillInTraining(result.data.kingdom_passives[0]);
            })
        }, (error: AxiosError) => {

        })
    }

    manageSuccessMessage(message: string) {
        this.setState({
            success_message: message
        })
    }

    closeSuccessAlert() {
        this.setState({
            success_message: null
        })
    }

    updatePassives(passives: any) {
        this.setState({
            kingdom_passives: passives
        }, () => {
            this.findSkillInTraining(passives[0]);
        });
    }

    findSkillInTraining(passive: any): void {
        if (passive.started_at !== null) {
            this.setState({
                skill_in_training: passive,
            });
        } else {
            if (passive.children.length > 0) {

                for (let i = 0; i < passive.children.length; i++) {
                    const child: any = passive.children[i];

                    if (child.started_at !== null) {
                        return this.setState({
                            skill_in_training: child,
                        });
                    } else {
                        if (child.children.length === 0) {
                            this.setState({
                                skill_in_training: null,
                            });

                            continue;
                        }

                        return this.findSkillInTraining(child);
                    }
                }
            } else {
                return this.setState({
                    skill_in_training: null,
                });
            }
        }
    }

    getTimeLeftInSeconds(): number {
        if (this.state.skill_in_training !== null) {
            const start = DateTime.now();
            const end = DateTime.fromISO(this.state.skill_in_training.completed_at);

            const diff = end.diff(start, ['seconds']).toObject();

            if (diff.hasOwnProperty('seconds')) {
                if (typeof diff.seconds !== 'undefined') {
                    return diff.seconds;
                }
            }

            return 0;
        }

        return 0;
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.loading ?
                        <div className={'relative p-10'}>
                            <ComponentLoading />
                        </div>

                    :
                        <div>
                            {
                                this.state.success_message !== null ?
                                    <div className='mb-4'>
                                        <SuccessAlert close_alert={this.closeSuccessAlert.bind(this)}>
                                            {this.state.success_message}
                                        </SuccessAlert>
                                    </div>
                                : null
                            }

                            <div className='mb-4'>
                                <InfoAlert>
                                    Click The skill name for additional actions. The timer will show below the tree when a skill is in progress.
                                </InfoAlert>
                            </div>
                            {
                                this.props.is_automation_running ?
                                    <div className='mb-4'>
                                        <WarningAlert>
                                            Automation is running. You cannot manage your passive skills.
                                        </WarningAlert>
                                    </div>
                                : null
                            }

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <KingdomPassiveTree passives={this.state.kingdom_passives[0]}
                                                manage_success_message={this.manageSuccessMessage.bind(this)}
                                                update_passives={this.updatePassives.bind(this)}
                                                character_id={this.props.character_id}
                                                is_dead={this.props.is_dead}
                                                is_automation_running={this.props.is_automation_running}
                            />

                            {
                                this.state.skill_in_training != null ?
                                    <div className='relative top-[24px]'>
                                        <TimerProgressBar time_out_label={'Skill In Training: ' + this.state.skill_in_training.name} time_remaining={this.getTimeLeftInSeconds()} />
                                    </div>
                                : null
                            }
                        </div>
                }
            </Fragment>
        )
    }

}
