<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class DeleteDuplicateCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteDuplicateCustomers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // Assuming $customer_db is your third-party connection instance
        $customer_db = DB::connection('mysql3')->table('customers');

        // Step 1: Find duplicate phone numbers using the DB facade
        $duplicatePhones = $customer_db
            ->select('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        // Step 2: Get all duplicate customers using the third-party connection
        $duplicateCustomers = DB::connection('mysql3')->table('customers')
            ->whereIn('phone', $duplicatePhones)
            ->select(['phone', 'id'])
            ->get();

        // Step 3: Fetch reports for all duplicate customers
        $customerIds = $duplicateCustomers->pluck('id')->toArray();

        // dd($customerIds);

        $reports = DB::connection('mysql3')->table('reports')
            ->whereIn('customer_id', $customerIds)
            ->select('id')
            ->get()
            ->groupBy('customer_id');

        // Step 4: Process duplicates to determine which to keep
        $customersToKeep = [];
        $customersToDelete = [];

        // Convert $duplicateCustomers to a collection for easier grouping
        $customersGroupedByPhone = $duplicateCustomers->groupBy('phone');

        foreach ($customersGroupedByPhone as $phone => $customers) {
            // Sort customers by the number of reports they have
            $customers = $customers->sortByDesc(function ($customer) use ($reports) {
                // Access the id using object property syntax
                return isset($reports[$customer->id]) ? count($reports[$customer->id]) : 0;
            });

            // Keep the first customer (the one with the most reports or any)
            $customersToKeep[] = $customers->first()->id;

            // Mark the rest for deletion
            $customersToDelete = array_merge($customersToDelete, $customers->skip(1)->pluck('id')->toArray());
        }

        // At this point, you have $customersToKeep and $customersToDelete ready for deletion or further processing
        // dd(count($duplicateCustomers), $customersToKeep, $customersToDelete);

        file_put_contents('customers_to_keep.txt', implode("\n", $customersToKeep));
        file_put_contents('customers_to_delete.txt', implode("\n", $customersToDelete));

        echo "Files have been created successfully.";

        echo "Total Duplicate Customers = " . count($duplicateCustomers);

        DB::connection('mysql3')->table('customers')
            ->whereIn('id', $customersToDelete)
            ->update(['deleted_at' => Carbon::now()]);
    }
}
