<?php

namespace App\Admin\Import\Npcs\Sheets;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\NpcCommand;
use App\Flare\Models\Npc;

class NpcCommandsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {

                $commandData = [
                    'npc_id'       => Npc::where('real_name', $row[0])->first()->id,
                    'command'      => $row[1],
                    'command_type' => $row[2],
                ];

                NpcCommand::UpdateOrCreate(['npc_id' => $commandData['npc_id']], $commandData);
            }
        }
    }
}
