<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class AssignOfficeIdInCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignOfficeIdInCustomers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Select 250 customers and save office id from their agent.';

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
        $customers = Customer::where('checked_for_office_id', false)->where('agent_id', '!=', null)->limit(600)->get();

        foreach ($customers as $key => $customer) {
            $office_id = null;

            if (isset($customer->agent) && isset($customer->agent->office) && isset($customer->agent->office->id)) {
                $office_id = $customer->agent->office->id;
            }

            if (!empty($office_id)) {
                $customer->office_id = $office_id;
            }

            
            $customer->checked_for_office_id = true;
            $customer->update();

            echo "Customer Id  = ". $customer->id;
            echo "\n";
            echo "Office Id  = ". $customer->office_id;
            echo "\n";
            echo "checked_for_office_id  = ". $customer->checked_for_office_id;
            echo "\n";
        }

        return 1;
    }
}
