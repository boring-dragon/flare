import LocationDetails from "../../../../map/types/location-details";

export interface LocationModalPros {
    is_open: boolean;

    handle_close: () => void;

    title: string;

    location: LocationDetails;

    character_position: {x: number, y: number};

    currencies: {gold: number, gold_dust: number, shards: number, copper_coins: number} | null;

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void;
}
