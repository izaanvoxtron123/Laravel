<?php

namespace App\Jobs;

use App\Imports\LeadsImport;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportCustomersCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($customers_csv)
    {
        $this->customers_csv = $customers_csv;
    }
    public $customers_csv;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $filePath = $this->customers_csv;
        $file = fopen($filePath, 'r');
        $data = [];
        while (($row = fgetcsv($file)) !== false) {
            $data[] = $row;
        }
        fclose($file);

        $headers = array_shift($data);
        $headers = preg_replace('/^[\x{FEFF}]+/u', '', $headers);

        $data = array_map(function ($row) use ($headers) {
            return array_combine($headers, $row);
        }, $data);


        Customer::insert($data);
        return;
    }
}
