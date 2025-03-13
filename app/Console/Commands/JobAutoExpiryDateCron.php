<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job; // Assuming your model is named Job
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class JobAutoExpiryDateCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:job-auto-expiry-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Log::info('cron is working');
        
        //$yesterdayEnd = Carbon::yesterday()->endOfDay();
        $jobs = Job::select('id', 'end_date')->where('status', 'active')->get();
        foreach ($jobs as $job) {
            if ($job->end_date < date("Y-m-d")) {
                $job->status = 'in_active';
                // Log::info("Job {$job->id} is inactive.");
                $job->save();
            }
        }
    
        return 0;
    }
    
}
