import KingdomDetails from "../../map/types/kingdom-details";

export default interface NpcKingdomsDetails extends KingdomDetails {
    id: number;

    x_position: number;

    y_position: number;

    npc_owned: boolean;
}
