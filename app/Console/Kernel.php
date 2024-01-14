<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\PaymentPlans;
use App\Models\YokassaSubscriptions as YokassaSubscriptionsModel;
use App\Http\Controllers\Gateways\YokassaController;
use Carbon\Carbon;
use App\Console\CustomScheduler;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $customSchedulerPath = app_path('Console/CustomScheduler.php');

        if (file_exists($customSchedulerPath)) {
            require_once($customSchedulerPath);
            CustomScheduler::scheduleTasks($schedule);
        }

        // $schedule->command('inspire')->hourly();
        $schedule->command(\Spatie\Health\Commands\RunHealthChecksCommand::class)->everyMinute();
        $schedule->call(function () {
            $activeSub_yokassa = YokassaSubscriptionsModel::where(['subscription_status', '=', 'active'])->get();
            foreach($activeSub_yokassa as $activeSub) {
                $data_now = Carbon::now();
                $data_end_sub = $activeSub->next_pay_at;
                if($data_now->gt($data_end_sub)) $result = YokassaController::handleSubscribePay($activeSub->id);
            }
        })->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
