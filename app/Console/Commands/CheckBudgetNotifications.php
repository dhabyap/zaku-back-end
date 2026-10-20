<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\BudgetNotificationService;

class CheckBudgetNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-budget-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check budget thresholds and create notifications';

    /**
     * Execute the console command.
     */
    public function handle(BudgetNotificationService $service): int
    {
        $created = 0;

        User::query()->chunkById(100, function ($users) use ($service, &$created) {
            foreach ($users as $user) {
                $created += $service->checkForUser($user);
            }
        });

        $this->info("Budget notification check completed. Created: {$created}");

        return self::SUCCESS;
    }
}