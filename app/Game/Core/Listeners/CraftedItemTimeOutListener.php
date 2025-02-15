<?php

namespace App\Game\Core\Listeners;

use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\ShowCraftingTimeOutEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Jobs\CraftTimeOutJob;

class CraftedItemTimeOutListener
{
    public function handle(CraftedItemTimeOutEvent $event)
    {

        $timeOut = 10;

        if (!is_null($event->extraTime)) {
            switch($event->extraTime) {
                case 'double':
                    $timeOut = 20;
                    break;
                case 'triple':
                    $timeOut = 30;
                    break;
            }
        }

        $event->character->update([
            'can_craft'          => false,
            'can_craft_again_at' => now()->addSeconds($timeOut),
        ]);

        broadcast(new ShowCraftingTimeOutEvent($event->character->user, $timeOut));

        broadcast(new UpdateCharacterStatus($event->character->refresh()));

        CraftTimeOutJob::dispatch($event->character)->delay(now()->addSeconds($timeOut));
    }
}
