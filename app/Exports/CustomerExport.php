<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CustomerExport implements FromView
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
        $customers = Customer::whereIn('id', $this->customer_ids)->get();
        return view('exports.customers', [
            'customers' => $customers
        ]);
    }
}
