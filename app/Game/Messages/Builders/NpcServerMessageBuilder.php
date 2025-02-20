<?php

namespace App\Game\Messages\Builders;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Npc;

class NpcServerMessageBuilder {

    /**
     * Build the server message
     *
     * @param string $type
     * @return string
     */
    public function build(string $type, Npc $npc, CelestialFight $celestialFight = null): string {
        switch ($type) {
            case 'took_kingdom':
                return $npc->real_name . ' smiles in your direction. "It\'s done!"';
            case 'kingdom_time_out':
                return $npc->real_name . ' looks disappointed as he looks at the ground and finally states: "No! You abandoned your last kingdom. You can wait..."';
            case 'cannot_have':
                return '"Sorry, you can\'t have that."';
            case 'too_poor':
                return '"I despise peasants! I spit on the ground before you! Come back when you can afford such treasures!"';
            case 'not_enough_gold':
                return '"I do not like dealing with poor people. You do not have the gold, child!"';
            case 'conjure':
                return $npc->real_name . '\'s Eyes light up as magic races through the air. "It is done, child!" he bellows and magic strikes the earth!';
            case 'take_a_look':
                return '"Why don\'t you take a look, and show me what you can afford, my child."';
            case 'location':
                return '"Child! You must come to me to make the exchange. Find me at (x/y): ' . $npc->x_position . '/' . $npc->y_position . ' ("'.$npc->gameMapName().'" Plane). Message me again when you are here."';
            case 'dead':
                return '"I don\'t deal with dead people. Resurrect, child."';
            case 'adventuring':
                return '"Child, you are adventuring. Come chat with me when you are NOT busy!"';
            case 'paid_conjuring':
                return $npc->real_name . ' takes your currency and smiles: "Thank you, child. I shall begin the conjuration at once."';
            case 'already_conjured':
                return '"No, child! I have already conjured for you!"';
            case 'missing_queen_item':
                return $npc->real_name . ' looks at you with a blank stare. You try again and she just refuses to talk to you or acknowledge you. Maybe you need a quest item? Something to do with: Queens Decision (Quest)???';
            case 'queen_plane':
                return $npc->real_name . ' looks at you, blinks here eyes and screams: "NO! NO! NO! You have to come to me, child. Come to me and let me love you... Oooooh hooo hoo hoo!" You must be anywhere in Hell to access her.';
            case 'public_exists':
                return '"No, child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"';
            case 'location_of_conjure':
                return '"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): '.$celestialFight->x_position.'/'.$celestialFight->y_position.' ('.$celestialFight->gameMapName().' Plane)"';
            case 'taken_item':
                return '"Child! You have an item I want! I shall take that."';
            case 'taken_second_item':
                return '"Child! You have another item I wanted! This is a good day for you!"';
            case 'has_plane_access':
                return '"Child, I see you have been exploring lately! This is a good thing!"';
            case 'has_faction_level':
                return '"Child! I see you have been keeping busy with the local wild life, gaining titles and all that. This is a good thing!"';
            case 'given_item':
                return '"Here child, take this! It might be of use to you!" (Check the help section under quest items to see what this does, or check your inventory and click on the item)';
            case 'inventory_full':
                return '"I cannot take the item from you, child! Your inventory is to full! Come back when you clean out some space."';
            case 'gold_capped':
                return '"Child! You are (or very close to being) Gold capped! You get no Gold.';
            case 'gold_dust_capped':
                return '"Child! You are (or are very close to being) Gold Dust capped! You get no Gold Dust.';
            case 'shard_capped':
                return '"Child! You are (or are very close to being) Shard capped! You get no Shards.';
            case 'currency_given':
                return '"I have payment for you, here take this!"';
            case 'quest_complete':
                return '"Pleasure doing business with you, child!"';
            case 'no_quests':
                return '"Sorry child, no work for you today. I either have nothing or you have a quest, but you might be missing something maybe? Check the requirements in: Plane Quests (beside character count in the map area)"';
            case 'no_skill':
                return '"Sorry child, I do not see a skill that needs unlocking."';
            case 'dont_own_skill':
                return '"Sorry child, you don\'t seem to own the skill to be unlocked!" (Chances are if you are seeing this, it\'s a bug. Head to discord post in the bugs section, link at the top)';
            case 'xp_given':
                return '"Here child, take this for your hard work!"';
            case 'skill_unlocked':
                return '"Child, I have done something magical! I have unlocked a skill for you!"';
            case 'take_currency':
                return '"Child! I shall take those shiny coins from you! I have something for you in return!"';
            case 'what_do_you_want':
                return '"Select something, child. One of those green items and tell me what you want. Remember I am not a cheap woman. You must please me to get what you want! I am the Queen of Hearts after all. Oooooh hooo hoo hoo!"';
            case 'missing_parent_quest':
                return '"Child! There is something you have to do, before you talk to me. Go do it!" (Open Plane Quests and find the quest you are trying to complete. Quests with lines connecting must be done in order).';
            case 'no_matching_command':
                return '"Huh?! What do you want, child!?! I don\'t have all day for these games, child! Spit it out!" ' . $npc->real_name . ' Seems annoyed by you. Maybe you misspoke?';
            case 'cant_afford_conjuring':
                return '"Why do these poor people always come to me?"
                ' . $npc->real_name . ' is not pleased with your lack of funds. try again when you can afford to be so brave.';
            default:
                return '';
        }
    }
}
