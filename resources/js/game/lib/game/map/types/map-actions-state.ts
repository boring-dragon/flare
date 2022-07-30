import LocationDetails from "./location-details";

export default interface MapActionsState {

    show_location_details: boolean;

    open_teleport_modal: boolean;

    player_kingdom_id: number | null;

    enemy_kingdom_id: number | null;

    npc_kingdom_id: number | null;

    location: LocationDetails | null;
}
