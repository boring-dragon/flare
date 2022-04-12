import InventoryComparisonAdjustment from "./inventory-comparison-adjustment";

export default interface InventoryItemComparisonState {

    comparison_details: {
        characterId: number;

        bowEquipped: boolean;

        hammerEquipped: boolean;

        setEquipped: boolean;

        setIndex: number;

        slotId: number;

        slotPosition: string | null;

        staveEquipped: boolean;

        type: string;

        itemToEquip: InventoryComparisonAdjustment;

        details: InventoryComparisonAdjustment[] | [];
    } | null;

    show_equip_modal: boolean;

    show_move_modal: boolean;

    action_loading: boolean;

    loading: boolean;
}
