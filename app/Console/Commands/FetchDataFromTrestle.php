<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class FetchDataFromTrestle extends Command
{
    protected $signature = 'fetchDataFromTrestle';
    protected $description = 'Go Through Completed Customers and fetch data from Trestle of 2,500 customers.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $rows = [];

        $customers = Customer::whereNotNull('first_name')
            ->whereNotNull('middle_initial')
            ->whereNotNull('last_name')
            ->whereNotNull('city')
            ->whereNotNull('state')
            ->whereNotNull('zip')
            ->whereNotNull('street_name')
            ->whereNotNull('house_number')
            ->limit(10)
            ->get();

        foreach ($customers as $customer) {
            $trestle_phone_numbers = [];

            $name = $customer->first_name . ' ' . $customer->middle_initial . ' ' . $customer->last_name;
            $original_phones = $customer->phone . ', ' . $customer->secondary_phones;
            $street_line_1 = $customer->street_name;
            $street_line_2 = $customer->house_number;
            $city = $customer->city;
            $postal_code = $customer->zip;
            $state_code = $customer->state;
            $country_code = "US";

            // Set your API key
            $api_key = 'cxPftAd4ivLKGJRWJLdHTC0qX8CmzrMuVBGB3Ax1jKG47Px3';

            // Build the URL
            $url = 'https://api.trestleiq.com/3.1/person?' . http_build_query([
                'name' => $name,
                'address.street_line_1' => $street_line_1,
                'address.street_line_2' => $street_line_2,
                'address.city' => $city,
                'address.postal_code' => $postal_code,
                'address.state_code' => $state_code,
                'address.country_code' => $country_code,
            ]);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'x-api-key: ' . $api_key
                ),
            ));

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            if ($response->count_person) {
                foreach ($response->person as $key =>  $person) {
                    if ($key >= 5) {
                        break;
                    }
                    foreach ($person->phones as $trestle_phone) {
                        // $trestle_phone_numbers[] =  str_replace(" ", "", str_replace("+1", "", $trestle_phone->phone_number));
                        $trestle_phone_numbers[] =   str_replace("+1", "", $trestle_phone->phone_number);
                    }
                }
            }
            $original_phones_array = explode(', ', str_replace("+1", "", $original_phones));

            // Find matched numbers
            $matched_numbers = array_intersect($original_phones_array, $trestle_phone_numbers);
            $rows[] = [
                'Name' => $name,
                'Original Phones' => $original_phones,
                'Trestle Phones' => implode(', ', $trestle_phone_numbers),
                'Matched Numbers' => $matched_numbers,
                'Matched Numbers Count' => count($matched_numbers)
            ];
        }

        if (empty($rows)) {
            Log::info('No data fetched from API');
            return 0;
        }

        $filePath = storage_path('app/customers_data.json');
        File::put($filePath, json_encode($rows)); // Convert $response to JSON string

        Log::info('fetchDataFromTrestle Command Ran');
        return 0;
    }
}
