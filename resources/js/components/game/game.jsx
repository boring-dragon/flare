import React               from 'react';
import ReactDOM            from 'react-dom';
import Chat                from './messages/chat';
import Map                 from './map/map';
import CharacterInfoTopBar from './components/character-info-top-bar';
import CoreActionsSection  from './components/core-actions-section';
import PortLocationActions from './components/port-location-actions';
import AdeventureActions from './components/adventure-actions';

class Game extends React.Component {
  constructor(props) {
    super(props);

    this.apiUrl = window.location.protocol + '//' + window.location.host + '/api/';

    this.state = {
      portDetails: {
        currentPort: null,
        portList: [],
        characterId: null,
        isDead: false,
        canMove: true,
      },
      adventureDetails: [],
      position: {},
      openPortDetails: false,
      openAdventureDetails: false,
    }
  }

  updatePort(portDetails) {
    this.setState({
      portDetails: portDetails,
    });
  }

  updateAdventure(adventureDetails) {
    this.setState({
      adventureDetails: adventureDetails,
    });
  }

  updatePlayerPosition(position) {
    this.setState({position: position});
  }

  openPortDetails(open) {
    this.setState({
      openPortDetails: open,
      openAdventureDetails: false,
    });
  }

  openAdventureDetails(open) {
    this.setState({
      openPortDetails: false,
      openAdventureDetails: open,
    });
  }

  render() {
    return (
      <>
        <div className="row mb-4">
          <div className="col-md-12">
            <div className="row">
              <div className="col-md-8">
                <CharacterInfoTopBar apiUrl={this.apiUrl} characterId={this.props.characterId} userId={this.props.userId}/>
                <CoreActionsSection apiUrl={this.apiUrl} userId={this.props.userId} />
                {this.state.openPortDetails ? <PortLocationActions portDetails={this.state.portDetails} userId={this.props.userId} openPortDetails={this.openPortDetails.bind(this)} updatePlayerPosition={this.updatePlayerPosition.bind(this)}/> : null}
                {this.state.openAdventureDetails ? <AdeventureActions adventureDetails={this.state.adventureDetails} userId={this.props.userId} openAdventureDetails={this.openAdventureDetails.bind(this)} /> : null}
              </div>
              <div className="col-md-4">
                <Map 
                  apiUrl={this.apiUrl}
                  userId={this.props.userId}
                  updatePort={this.updatePort.bind(this)}
                  position={this.state.position}
                  updatePlayerPosition={this.updatePlayerPosition.bind(this)}
                  openPortDetails={this.openPortDetails.bind(this)}
                  openAdventureDetails={this.openAdventureDetails.bind(this)}
                  updateAdventure={this.updateAdventure.bind(this)}
                />
              </div>
            </div>
          </div>
        </div>
        <div className="row">
          <div className="col-md-12">
            <Chat apiUrl={this.apiUrl} userId={this.props.userId}/>
          </div>
        </div>
      </>
    )
  }
}

// Mount the app:
const game      = document.getElementById('game');
const player    = document.head.querySelector('meta[name="player"]');
const character = document.head.querySelector('meta[name="character"]');

if (game !== null) {
  ReactDOM.render(
      <Game userId={parseInt(player.content)} characterId={character.content}/>,
      game
  );
}
