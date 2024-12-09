<?php

namespace App\Exports;

use App\Models\Leadcenter;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LeadcenterExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function __construct(array $lead_ids)
    {
        $this->lead_ids = $lead_ids;
    }
    public $lead_ids;


    public function view(): View
    {
        $leads = Leadcenter::whereIn('id', $this->lead_ids)->get();
        return view('exports.leadcenter', [
            'leads' => $leads
        ]);
    }
}
