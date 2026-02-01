<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class BustProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:cache-bust
                            {--reset : Reset version to 1 instead of incrementing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bust product cache by incrementing cache version (forces POS and inventory to reload fresh stock data)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('reset')) {
            Cache::forever('products_stock_version', 1);
            $this->info('✅ Product cache version reset to 1');
        } else {
            $currentVersion = Cache::get('products_stock_version', 1);
            $newVersion = $currentVersion + 1;
            Cache::forever('products_stock_version', $newVersion);
            $this->info("✅ Product cache version incremented: {$currentVersion} → {$newVersion}");
        }

        $this->comment('POS and inventory pages will now load fresh stock data on next request.');

        return Command::SUCCESS;
    }
}
