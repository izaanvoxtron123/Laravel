<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkOfflineInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'markOfflineInactiveUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for inactive users and mark them as offline.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentUnixTime = time();
        $inactivity_timeout = 120 * 60;
        $inactivity_timestamp = $currentUnixTime - $inactivity_timeout;

        // Query the sessions table using the DB facade
        $query =  DB::table('sessions')
            ->where('last_activity', '<=', $inactivity_timestamp)
            ->whereNotNull('user_id');
        $inactive_users = $query->get();

        foreach ($inactive_users as $key => $inactive_user) {
            User::where('id', $inactive_user->user_id)->update(['is_online' => 0]);
        }
        $inactive_users = $query->delete();

        return 0;
    }
}
