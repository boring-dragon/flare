import React from 'react';
import {Tabs, Tab} from 'react-bootstrap';
import InventorySection from "./sections/inventory-section";

export default class InventoryBase extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      inventory: {},
    }
  }

  getSlotId(itemId) {
    const foundSlot = this.state.inventory.inventory.filter((i) => i.item.id === itemId);

    if (foundSlot.length > 0) {
      return foundSlot[0].id
    }

    return null;
  }

  componentDidMount() {
    axios.get('/api/character/'+this.props.characterId+'/inventory')
      .then((result) => {
        this.setState({
          loading: false,
          inventory: result.data,
        });
      }).catch((error) => {
        if (error.hasOwnProperty('response')) {
          const response = error.response;

          if (response.status === 401) {
            return location.reload()
          }

          if (response.status === 429) {
            return window.location.replace('/game');
          }
        }
      });
  }

  render() {
    return (
      <>
        {
          this.state.loading ?
            <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
          :
            <Tabs defaultActiveKey="inventory" id="inventory-section">
              <Tab eventKey="inventory" title="Inventory">
                <InventorySection
                  characterId={this.props.characterId}
                  inventory={this.state.inventory.inventory}
                  getSlotId={this.getSlotId.bind(this)}
                />
              </Tab>
              <Tab eventKey="equipped" title="Equipped">
                Equipped Component Here
              </Tab>
              <Tab eventKey="sets" title="Sets">
                Sets Component Here
              </Tab>
              <Tab eventKey="quest-items" title="Quest Items">
                Quest Items Component Here
              </Tab>
            </Tabs>
        }
      </>
    )
  }
}
