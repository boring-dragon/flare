import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomProps from "../../lib/game/kingdoms/types/kingdom-props";
import KingdomDetails from "./kingdom-details";
import Select from "react-select";
import BuildingInQueueDetails from "../../lib/game/kingdoms/building-in-queue-details";
import SmallBuildingsSection from "./buildings/small-buildings-section";
import SmallUnitsSection from "./units/small-units-section";
import DangerButton from "../../components/ui/buttons/danger-button";
import SmallKingdomState from "../../lib/game/kingdoms/types/small-kingdom-state";

export default class SmallKingdom extends React.Component<KingdomProps, SmallKingdomState> {

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            show_kingdom_details: false,
            which_selected: null,
        }
    }

    manageKingdomDetails() {
        this.setState({
            show_kingdom_details: !this.state.show_kingdom_details,
        });
    }

    showSelected(data: any) {
        this.setState({
            which_selected: data.value
        });
    }

    closeSelected() {
        this.setState({
            which_selected: null
        })
    }

    renderSelected() {
        switch(this.state.which_selected) {
            case 'buildings':
                return <SmallBuildingsSection
                    kingdom={this.props.kingdom}
                    dark_tables={this.props.dark_tables}
                    close_selected={this.closeSelected.bind(this)}
                    character_gold={this.props.character_gold}
                />
            case 'units':
                return <SmallUnitsSection
                    kingdom={this.props.kingdom}
                    dark_tables={this.props.dark_tables}
                    close_selected={this.closeSelected.bind(this)}
                    character_gold={this.props.character_gold}
                />
            default:
                return null
        }
    }

    render() {
        return (
            <Fragment>
                <BasicCard>
                    {
                        !this.state.show_kingdom_details ?
                            <div className='grid grid-cols-2'>
                                <span><strong>Kingdom  Details</strong></span>
                                <div className='text-right cursor-pointer text-blue-500'>
                                    <button onClick={this.manageKingdomDetails.bind(this)}><i className="fas fa-plus-circle"></i></button>
                                </div>
                            </div>
                        :
                            <Fragment>
                                <div className='grid grid-cols-2 mb-5'>
                                    <span><strong>Kingdom  Details</strong></span>
                                    <div className='text-right cursor-pointer text-red-500'>
                                        <button onClick={this.manageKingdomDetails.bind(this)}><i className="fas fa-minus-circle"></i></button>
                                    </div>
                                </div>

                                <KingdomDetails kingdom={this.props.kingdom} character_gold={this.props.character_gold} close_details={this.props.close_details} />
                            </Fragment>
                    }
                </BasicCard>

                <div className='mt-4'>
                    {
                        this.state.which_selected !== null ?
                            this.renderSelected()
                        :
                            <Fragment>
                                <Select
                                    onChange={this.showSelected.bind(this)}
                                    options={[
                                        {
                                            label: 'Building Management',
                                            value: 'buildings',
                                        },
                                        {
                                            label: 'Unit Management',
                                            value: 'units',
                                        }
                                    ]}
                                    menuPosition={'absolute'}
                                    menuPlacement={'bottom'}
                                    styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                    menuPortalTarget={document.body}
                                    value={[
                                        {label: 'Please Select Section', value: ''}
                                    ]}
                                />
                                <div className='grid gap-3'>
                                    <DangerButton button_label={'Close'} on_click={this.props.close_details} additional_css={'mt-4'} />
                                </div>
                            </Fragment>
                    }
                </div>
            </Fragment>
        )
    }
}
