<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Game\Kingdoms\Service\UpdateKingdom;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Requests\PurchaseGoldBarsRequest;
use App\Game\Kingdoms\Requests\WithdrawGoldBarsRequest;

class KingdomGoldBarsController extends Controller {

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @param UpdateKingdom $updateKingdom
     */
    public function __construct(UpdateKingdom $updateKingdom){
        $this->updateKingdom = $updateKingdom;
    }

    /**
     * @param PurchaseGoldBarsRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function purchaseGoldBars(PurchaseGoldBarsRequest $request, Kingdom $kingdom): JsonResponse {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        $amountToBuy = $request->amount_to_purchase;

        if ($amountToBuy > 1000) {
            $amountToBuy = 1000;
        }

        $newGoldBars = $amountToBuy + $kingdom->gold_bars;

        if ($newGoldBars > 1000) {
            $amountToBuy = $amountToBuy - $kingdom->gold_bars;
        }

        $cost = $amountToBuy * 2000000000;

        $character = $kingdom->character;

        if ($cost > $character->gold) {
            return response()->json(['message' => 'Not enough gold.'], 422);
        }

        $character->update([
            'gold' => $character->gold - $cost
        ]);

        $kingdom->update([
            'gold_bars' => $newGoldBars,
        ]);

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json([
            'message' => 'Purchased: ' . number_format($amountToBuy) . ' Gold bars.'
        ], 200);
    }

    /**
     * @param WithdrawGoldBarsRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function withdrawGoldBars(WithdrawGoldBarsRequest $request, Kingdom $kingdom): JsonResponse {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        $amount = $request->amount_to_withdraw;

        if ($kingdom->gold_bars < $amount) {
            $amount = $kingdom->gold_bars;
        }

        $totalGold = $amount * 2000000000;
        $character = $kingdom->character;

        $newGold = $character->gold + $totalGold;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            return response()->json([
                'message' => 'You would waste gold if you withdrew this amount.'
            ], 422);
        }

        $character->update([
            'gold' => $newGold,
        ]);

        $kingdom->update([
            'gold_bars' => $kingdom->gold_bars - $amount,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json([
            'message' => 'Exchanged: ' . $amount . ' Gold bars for: ' . number_format($totalGold) . ' Gold!',
        ], 200);
    }
}
