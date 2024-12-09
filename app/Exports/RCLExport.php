<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RCLExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function __construct(array $customer_ids)
    {
        $this->customer_ids = $customer_ids;
    }
    public $customer_ids;

    public function view(): View
    {
        $customers = Customer::whereIn('id', $this->customer_ids)->with('phones')->get();
        return view('exports.rcl', [
            'customers' => $customers
        ]);
    }

    public function chunkSize(): int
    {
        return 1000; // Export records in chunks of 1000
    }
}
