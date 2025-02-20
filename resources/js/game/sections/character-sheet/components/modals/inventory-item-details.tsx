import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ItemNameColorationText from "../../../../components/ui/item-name-coloration-text";
import {AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import ItemDetails from "./components/item-details";
import InventoryQuestItemDetails from "./components/inventory-quest-item-details";

export default class InventoryUseDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            item: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/item/' + this.props.item_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                item: result.data,
            });
        }, (error: AxiosResponse) => {

        })
    }

    modalTitle() {
        if (this.state.loading) {
            return 'Fetching item details ...';
        }

        return <ItemNameColorationText item={this.state.item} />;
    }

    largeModal() {
        if (this.state.item !== null) {
            return this.state.item.type !== 'quest';
        }

        return false;
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.modalTitle()}
                      large_modal={this.largeModal()}
                      additional_dialogue_css={'top-[110px]'}
            >
                <div className="mb-5 relative">
                    {
                        this.state.loading ?
                            <div className='py-10'>
                                <ComponentLoading />
                            </div>
                        :
                            <Fragment>
                                {
                                    this.state.item.type === 'quest' ?
                                        <InventoryQuestItemDetails item={this.state.item} />
                                    :
                                        <ItemDetails item={this.state.item} />
                                }
                            </Fragment>
                    }
                </div>
            </Dialogue>
        );
    }
}
