<?php

namespace App\Flare\Services;

use Cache;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Events\GlobalMessageEvent;

class DailyGoldDustService {

    const LOTTO_MAX = 10000;

    /**
     * Handle regular amounts of gold dust.
     *
     * @param Character $character
     * @return void
     */
    public function handleRegularDailyGoldDust(Character $character) {
        $amount = rand(1, 100);

        $newAmount = $character->gold_dust + $amount;

        if ($newAmount >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $newAmount = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $newAmount,
        ]);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'daily_lottery', $amount));

        event(new UpdateTopBarEvent($character));
    }

    /**
     * Handle the winner  of daily gold dust.
     *
     * @param Character $character
     * @return void
     */
    public function handleWonDailyLottery(Character $character) {

        if (!Cache::has('daily-gold-dust-lottery-won')) {
            event(new GlobalMessageEvent($character->name . ' has won the daily Gold Dust Lottery!
            (Gold Dust is used in Alchemy and Quests - See Help section -> Click Help I\'m stuck, and see currencies under: Character Information -> Currencies)'
            ));

            $newAmount = $character->gold_dust + self::LOTTO_MAX;

            if ($newAmount >= MaxCurrenciesValue::MAX_GOLD_DUST) {
                $newAmount = MaxCurrenciesValue::MAX_GOLD_DUST;
            }

            $character->update([
                'gold_dust' => $newAmount,
            ]);

            $character = $character->refresh();

            Cache::put('daily-gold-dust-lottery-won', $character->id);
        }

        event(new ServerMessageEvent($character->user, 'lotto_max', self::LOTTO_MAX));

        event(new UpdateTopBarEvent($character));
    }
}
