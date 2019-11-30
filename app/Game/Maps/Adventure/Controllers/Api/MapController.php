<?php

namespace App\Game\Maps\Adventure\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Map;
use App\User;

class MapController extends Controller {

    public function __construct() {
        $this->middleware('auth:api');
    }

    public function index(Request $request, User $user) {
        return response()->json([
            'map_url' => asset('/storage/surface.png'),
            'character_map' => $user->character->map,
            'character_id'  => $user->character->id,
        ]);
    }

    public function move(Request $request, Character $character) {
        $character->map->update([
            'character_position_x' => $request->character_position_x,
            'character_position_y' => $request->character_position_y,
            'position_x'           => $request->position_x,
            'position_y'           => $request->position_y,
        ]);

        return response()->json([], 200);
    }
}
