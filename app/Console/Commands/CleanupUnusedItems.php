<?php

namespace App\Console\Commands;

use App\Flare\Models\HolyStack;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\SetSlot;
use Illuminate\Console\Command;

class CleanupUnusedItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:unused-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans items from the database';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {

        ini_set('memory_limit','3G');

        $this->line('Cleaning up items ...');

        $prefixItems = Item::whereHas('itemPrefix')
                           ->whereDoesntHave('inventorySlots')
                           ->whereDoesntHave('inventorySetSlots')
                           ->whereDoesntHave('marketListings')
                           ->whereDoesntHave('marketHistory')
                           ->pluck('id')->toArray();

        $prefixCount = count($prefixItems);

        $this->line('Cleaning Prefixes ('.$prefixCount.') ...');
        $this->newLine();

        $chunkedPrefixItems = array_chunk($prefixItems, 10);

        $bar = $this->output->createProgressBar(count($chunkedPrefixItems));

        foreach ($chunkedPrefixItems as $chunk) {
            HolyStack::whereIn('item_id', $chunk)->delete();
            Item::whereIn('id', $chunk)->delete();

            $bar->advance();
        }

        $suffixItems = Item::whereHas('itemSuffix')
                           ->whereDoesntHave('inventorySlots')
                           ->whereDoesntHave('inventorySetSlots')
                           ->whereDoesntHave('marketListings')
                           ->whereDoesntHave('marketHistory')
                           ->pluck('id')->toArray();

        $this->line('Cleaning Suffixes ('.count($suffixItems).') ...');
        $this->newLine();

        $chunkedSuffixItems = array_chunk($suffixItems, 10);

        $bar = $this->output->createProgressBar(count($chunkedSuffixItems));

        foreach ($chunkedSuffixItems as $chunk) {
            HolyStack::whereIn('item_id', $chunk)->delete();
            Item::whereIn('id', $chunk)->delete();

            $bar->advance();
        }

        $this->line('All Done ...');
    }
}
