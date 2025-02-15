import CharacterSheetProps from "../character-sheet-props";
import {CharacterType} from "../../../character/character-type";

export interface AdditionalInfoModalProps extends CharacterSheetProps {

    is_open: boolean;

    manage_modal: () => void;

    title: string;

    character: CharacterType | null

}

