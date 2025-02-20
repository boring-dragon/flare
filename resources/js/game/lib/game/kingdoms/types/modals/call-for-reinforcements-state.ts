import KingdomReinforcementType from "../kingdom-reinforcement-type";
import SelectedUnitsToCallType from "../selected-units-to-call-type";

export default interface CallForReinforcementsState {

    loading: boolean;

    processing_unit_request: boolean;

    kingdoms: KingdomReinforcementType[]|[];

    error_message: string;

    success_message: string;

    selected_kingdoms: number[];

    selected_units: SelectedUnitsToCallType[]|[];
}
