<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\GuideQuests\GuideQuestsExport;

use App\Admin\Import\GuideQuests\GuideQuests;
use App\Admin\Requests\GuideQuestManagement;
use App\Admin\Requests\GuideQuestsImport;
use App\Admin\Services\GuideQuestService;
use App\Flare\Models\GuideQuest;

use App\Flare\Models\PassiveSkill;
use App\Http\Controllers\Controller;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use Maatwebsite\Excel\Facades\Excel;

class GuideQuestsController extends Controller {

    private GuideQuestService $guideQuestService;

    public function __construct(GuideQuestService $guideQuestService) {
        $this->guideQuestService = $guideQuestService;
    }

    public function index() {
        return view('admin.guide-quests.index');
    }

    public function export() {
        $response = Excel::download(new GuideQuestsExport(), 'guide-quests.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    public function import(GuideQuestsImport $request) {
        Excel::import(new GuideQuests, $request->guide_quests_import);

        return redirect()->back()->with('success', 'imported guide quest data.');
    }

    public function exportGuideQuests() {
        return view('admin.guide-quests.export');
    }

    public function importGuideQuests() {
        return view('admin.guide-quests.import');
    }

    public function show(GuideQuest $guideQuest) {
        return view('admin.guide-quests.show', [
            'guideQuest' => $guideQuest,
        ]);
    }

    public function create() {
        return view('admin.guide-quests.manage', [
            'guideQuest'  => null,
            'gameSkills'  => GameSkill::pluck('name', 'id')->toArray(),
            'gameMaps'    => GameMap::where('name', '!=', 'Purgatory')->pluck('name', 'id')->toArray(),
            'quests'      => Quest::pluck('name', 'id')->toArray(),
            'questItems'  => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'passives'    => PassiveSkill::pluck('name', 'id')->toArray()
        ]);
    }

    public function store(GuideQuestManagement $request) {
        $params = $this->guideQuestService->cleanRequest($request->all());

        $params['instructions'] = str_replace('<p><br></p>', '', $params['instructions']);

        $guideQuest = GuideQuest::updateOrCreate(['id' => $params['id']], $params);

        return response()->redirectToRoute('admin.guide-quests.show', ['guideQuest' => $guideQuest->id])->with('success', 'Saved Guide Quest');
    }

    public function edit(GuideQuest $guideQuest) {
        return view('admin.guide-quests.manage', [
            'guideQuest'  => $guideQuest,
            'gameSkills'  => GameSkill::pluck('name', 'id')->toArray(),
            'gameMaps'    => GameMap::where('name', '!=', 'Purgatory')->pluck('name', 'id')->toArray(),
            'quests'      => Quest::pluck('name', 'id')->toArray(),
            'questItems'  => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'passives'    => PassiveSkill::pluck('name', 'id')->toArray()
        ]);
    }

    public function delete(GuideQuest $guideQuest) {
        $guideQuest->delete();

        return response()->redirectToRoute('admin.guide-quests')->with('success', 'Deleted guide quest.');
    }
}
