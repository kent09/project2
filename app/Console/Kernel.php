<?php

namespace App\Console;

use App\Console\Commands\JWTSecretKeyGenerator;
use App\Console\Commands\TaskSlugCrawler;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        
        # Unscheduled Cronjobs
        JWTSecretKeyGenerator::class,
        TaskSlugCrawler::class,
        \Laravelista\LumenVendorPublish\VendorPublishCommand::class,
        \App\Console\Commands\SettingsDefault::class,
        \App\Console\Commands\SaveBankHistory::class,
        \App\Console\Commands\Bonus::class,
        \App\Console\Commands\WithdrawalCheck::class,
        \App\Console\Commands\VerifyEmailReminder::class,
        \App\Console\Commands\CheckSecurityItems::class,
        \App\Console\Commands\CalculateReferralPointsV2::class,
        \App\Console\Commands\SaveReferralByLevel::class,
        
        
        # Scheduled Cronjobs
        \App\Console\Commands\SortOwnLeaderboard::class,
        \App\Console\Commands\SortTopUsers::class,
        \App\Console\Commands\CalculateReferralPoints::class,
        \App\Console\Commands\RecalculateReferralReward::class,
        \App\Console\Commands\SendSupPayments::class,
        \App\Console\Commands\CheckVisitor::class,
        \App\Console\Commands\UpdateUserVotes::class,
        \App\Console\Commands\TruncateJobIfEmpty::class,
        \App\Console\Commands\CheckMembershipValidity::class,
        \App\Console\Commands\GetExternalDeposits::class,
        
        
        
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:suppayment')->everyMinute()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/send-suppayment.log'));
        $schedule->command('visitor:check')->everyMinute()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/visitor-check.log'));
        $schedule->command('votes:update')->hourly()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/votes-update.log'));
        $schedule->command('jobs:truncate')->hourly()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/jobs-truncate.log'));
        $schedule->command('email:verification')->daily()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/email-verification.log'));
        $schedule->command('check:security')->daily()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/check-security.log'));

        // Should be called as one
        $schedule->command('referral:calculate')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/referral-calculate.log'));
        $schedule->command('referral:reward')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/referral-reward.log'));
        $schedule->command('sort:referral')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/sort-referral.log'));
        $schedule->command('sort:own')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/sort-own.log'));
        $schedule->command('referral:level')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/referral-level.log'));

        $schedule->command('membership:validate')->daily()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/membership-validate.log'));
        $schedule->command('deposit:process')->everyFiveMinutes()->withoutOverlapping(3)->sendOutputTo(storage_path('logs/deposit-process.log'));
    }
}
