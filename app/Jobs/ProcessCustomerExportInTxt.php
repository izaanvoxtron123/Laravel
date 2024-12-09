<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Customer as MainModel;
use App\Models\CustomerExports;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCustomerExportInTxt implements ShouldQueue
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
        $customers = MainModel::whereIn('id', $this->customer_ids)->with(['phones', 'addresses', 'accounts'])->get();
        $batchname = "batch-" . Carbon::now()->format('m-d-Y_H-i_A');
        // $zip = Zip::create(asset('storage/customer_exports/zips/'.$batchname.'.zip'));
        $content = "";

        foreach ($customers as $customer) {
            if (!empty($customer->first_name)) $content .= "First name : " . $customer->first_name . "\n";
            if (!empty($customer->last_name)) $content .= "Last name : " . $customer->last_name . "\n";
            if (!empty($customer->phone)) $content .= "Phone : " . $customer->phone . "\n";

            // if (!empty($customer->secondary_phones)) {
            $content .= "Secondary Phones : " . $customer->secondary_phones . "\n";
            foreach ($customer->phones->where('is_primary', '1') as $key => $phone) {
                if (!empty($phone->phone_number)) $content .= $phone->phone_number . "\n";
            }
            // }

            if (!empty($customer->house_number)) $content .= "House number : " . $customer->house_number . "\n";
            if (!empty($customer->street_name)) $content .= "Street name : " . $customer->street_name . "\n";
            if (!empty($customer->street_type)) $content .= "Street type : " . $customer->street_type . "\n";
            if (!empty($customer->city)) $content .= "City : " . $customer->city . "\n";
            if (!empty($customer->state)) $content .= "State : " . $customer->state . "\n";
            if (!empty($customer->zip)) $content .= "Zip : " . $customer->zip . "\n";

            if (!empty($customer->addresses)) {
                $content .= "\nSecondary Addresses : \n";
                foreach ($customer->addresses as $key => $address) {
                    if (!empty($address->house_number)) $content .= "House number : " . $address->house_number . "\n";
                    if (!empty($address->street_name)) $content .= "Street name : " . $address->street_name . "\n";
                    if (!empty($address->street_type)) $content .= "Street type : " . $address->street_type . "\n";
                    if (!empty($address->city)) $content .= "City : " . $address->city . "\n";
                    if (!empty($address->state)) $content .= "State : " . $address->state . "\n";
                    if (!empty($address->zip)) $content .= "Zip : " . $address->zip . "\n";
                }
                $content .= "\n";
            }

            if (!empty($customer->ssn)) $content .= "Social security : " . $customer->ssn . "\n";
            if (!empty($customer->dob)) $content .= "Date of birth : " . Carbon::parse($customer->dob)->isoFormat('MM/DD/YYYY') . "\n";
            if (!empty($customer->mmn)) $content .= "MMN : " . $customer->mmn . "\n";
            if (!empty($customer->email)) $content .= "Email : " . $customer->email . "\n";


            if (!empty($customer->accounts)) {
                $content .= "\nBANKING: \n";
                foreach ($customer->accounts->sortByDesc('charge_card') as $key => $card) {
                    if (!empty($card->charge)) $content .= "Charge on this card : " . $card->charge . "\n";
                    if (isset($card->charge_card)) $content .= "Charge Card : " . ($card->charge_card ? 'Yes' : 'No') . "\n";
                    if (!empty($card->noc)) $content .= "NOC : " . $card->noc . "\n";
                    if (!empty($card->account_name)) $content .= "Bank Name : " . $card->account_name . "\n";
                    if (!empty($card->exp)) $content .= "Exp : " . $card->exp . "\n";
                    if (!empty($card->account_number)) $content .= "Card # : " . preg_replace('/(\d{4})(?=\d)/', '$1-', $card->account_number) . "\n";
                    if (!empty($card->cvv1)) $content .= "CVV/CVV (First CVV) : " . $card->cvv1 . "\n";
                    if (!empty($card->balance)) $content .= "Balance : " . $card->balance . "\n";
                    if (!empty($card->available)) $content .= "Available : " . $card->available . "\n";
                    if (!empty($card->lp)) $content .= "LP : " . $card->lp . "\n";
                    if (!empty($card->dp)) $content .= "DP : " . $card->dp . "\n";
                    if (!empty($card->apr)) $content .= "APR% : " . $card->apr . "\n";
                    if (!empty($card->poa)) $content .= "POA : " . $card->poa . "\n";
                    if (!empty($card->full_name)) $content .= "Full Name : " . $card->full_name . "\n";
                    if (!empty($card->ssn)) $content .= "SSN : " . $card->ssn . "\n";
                    if (!empty($card->mmm)) $content .= "Mmm : " . $card->mmm . "\n";
                    if (!empty($card->dob)) $content .= "DOB : " . $card->dob . "\n";
                    if (!empty($card->relation)) $content .= "Relation : " . $card->relation . "\n";

                    // if (!empty($card->toll_free)) $content .= "Toll Free : " . $card->toll_free . "\n";
                    // if (!empty($card->cvv2)) $content .= "CVV/CVV (Second CVV) : " . $card->cvv2 . "\n";

                    $content .= "\n";
                }
            }


            if (!empty($customer->no_of_oc)) $content .= "Total Cards : " . $customer->no_of_oc . "\n";
            if (!empty($customer->td)) $content .= "Total Debt : " . $customer->td . "\n";
            if (!empty($customer->charge)) $content .= "Total Charge : " . $customer->charge . "\n";


            if (!empty($customer->agent)) $content .= "Agent  : " . $customer->agent->name . "\n";
            if (!empty($customer->to_person)) $content .= "TO  : " . $customer->to_person->name . "\n";
            if (!empty($customer->closer)) $content .= "CLOSER  : " . $customer->closer->name . "\n";


            if (!empty($customer->meta)) $content .= "\nMetaData: \n" . $customer->meta . "\n";

            // if (!empty($customer->no_of_ac)) $content .= "No. of Accounts : " . $customer->no_of_ac . "\n";
            // if (!empty($customer->ta)) $content .= "Total Available : " . $customer->ta . "\n";
            // if (!empty($customer->d_to_ir)) $content .= "Debt to Income Ratio % : " . $customer->d_to_ir . "\n";
            // if (!empty($customer->progress)) $content .= "Progress : " . $customer->progress . "\n";
            // if (!empty($customer->meta)) $content .= "\nMetadata \n" . $customer->meta;
            // if (!empty($customer->score)) $content .= "Score : " . $customer->score . "\n";
            // $content .= ("Comments : " . $customer->comments) . "\n";


            $content .= "\n***************************\n \n";
        }

        if (Storage::disk('public')->put('customer_exports/' . $batchname . '.txt', $content)) {
            CustomerExports::create([
                'path' => 'customer_exports/' . $batchname . '.txt',
                'filename' => $batchname . '.txt',
            ]);
        }
    }
}
