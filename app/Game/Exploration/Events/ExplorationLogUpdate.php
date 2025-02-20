<?php

namespace App\Game\Exploration\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Flare\Models\User;

class ExplorationLogUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private $user;

    /**
     * @var string $message
     */
    public $message;

    public $makeItalic;

    public $isReward;

    /**
     * @param User $user
     * @param string $message
     */
    public function __construct(User $user, string $message, bool $makeItalic = false, bool $isReward = false)
    {
        $this->user       = $user;
        $this->message    = $message;
        $this->makeItalic = $makeItalic;
        $this->isReward   = $isReward;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('exploration-log-update-' . $this->user->id);
    }
}
