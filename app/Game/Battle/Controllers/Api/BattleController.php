<?php

namespace App\Game\Battle\Controllers\Api;

use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Core\Events\AttackTimeOutEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Http\Controllers\Controller;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Flare\Handlers\CheatingCheck;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Models\User;
use App\Flare\Transformers\MonsterTransfromer;

class BattleController extends Controller {

    private $manager;

    private $character;

    private $monster;

    public function __construct(Manager $manager, CharacterAttackTransformer $character, MonsterTransfromer $monster, BattleEventHandler $battleEventHandler) {
        $this->middleware('is.character.dead')->except(['revive', 'index']);
        $this->middleware('is.character.adventuring')->except(['index']);

        $this->manager            = $manager;
        $this->character          = $character;
        $this->monster            = $monster;
        $this->battleEventHandler = $battleEventHandler;
    }

    public function index(Request $request) {
        $user           = User::find($request->user_id);

        if ($user->id !== auth()->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to do that.'
            ], 422);
        }

        $foundCharacter = $user->character;
        $character      = new Item($foundCharacter, $this->character);
        $monsters       = Cache::get('monsters')[$foundCharacter->map->gameMap->name];

        return response()->json([
            'monsters'  => $monsters,
            'character' => $this->manager->createData($character)->toArray(),
        ], 200);
    }

    public function battleResults(Request $request, Character $character) {
        if (!$character->can_attack) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        if ($request->is_character_dead) {

            $this->battleEventHandler->processDeadCharacter($character);

            return response()->json([], 200);
        }

        if ($request->is_defender_dead) {

            switch ($request->defender_type) {
                case 'monster':
                    event(new AttackTimeOutEvent($character));
                    BattleAttackHandler::dispatch($character, $request->monster_id);
                    break;
                default:
                    return response()->json([
                        'message' => 'Could not find type of defender.'
                    ], 422);
            }
        }

        return response()->json([], 200);
    }

    public function revive(Character $character) {
        $character = $this->battleEventHandler->processRevive($character);

        $character = new Item($character, $this->character);

        return response()->json([
            'character' => $this->manager->createData($character)->toArray()
        ], 200);
    }

}
