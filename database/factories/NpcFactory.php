<?php

namespace Database\Factories;

use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

class NpcFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Npc::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'                        => 'SampleNpc',
            'type'                        => NpcTypes::KINGDOM_HOLDER,
            'game_map_id'                 => 1,
            'moves_around_map'            => false,
            'must_be_at_same_location'    => false,
            'text_command_to_message'     => 'Take Kingdom',
            'x_position'                  => 32,
            'y_position'                  => 144,
        ];
    }
}
