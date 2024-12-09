<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Leadcenter as MainModel;
use App\Models\CustomerExports;
use Illuminate\Support\Facades\Storage;

class ProcessLeadcenterExportInTxt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $customer_ids)
    {
        $this->customer_ids = $customer_ids;
    }
    public $customer_ids;


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $leads = MainModel::whereIn('id', $this->customer_ids)->get();
        $batchname = "leads-batch-" . Carbon::now()->format('m/d/Y');

        // $zip = Zip::create(asset('storage/customer_exports/zips/'.$batchname.'.zip'));
        $content = "";

        foreach ($leads as $lead) {

            $content .= ("First name : " . $lead->first_name) . "\n";
            $content .= ("Surame : " . $lead->surname) . "\n";
            $content .= ("Phone : " . $lead->phone) . "\n";
            $content .= ("Email : " . $lead->email) . "\n";
            $content .= ("Social security : " . $lead->ssn) . "\n";
            $content .= ("Street Abbr : " . $lead->state_abbr) . "\n";
            $content .= ("City : " . $lead->city) . "\n";
            $content .= ("Street : " . $lead->street) . "\n";
            $content .= ("Zip : " . $lead->zip) . "\n";
            $content .= ("Score : " . $lead->score) . "\n";
            $content .= ("No of OC : " . $lead->no_of_oc) . "\n";
            $content .= ("No of AC : " . $lead->no_of_ac) . "\n";
            $content .= ("TD : " . $lead->td) . "\n";
            $content .= ("TA : " . $lead->ta) . "\n";
            $content .= ("D to IR : " . $lead->d_to_ir) . "\n";

            $content .= "\n***************************\n \n";
        }

        if (Storage::disk('public')->put('leadcenter_exports/' . $batchname . '.txt', $content)) {
            CustomerExports::create([
                'path' => 'leadcenter_exports/' . $batchname . '.txt',
                'filename' => $batchname. '.txt',
            ]);
        }
    }
}
