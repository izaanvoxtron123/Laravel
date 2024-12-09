<?php

namespace App\Jobs;

use App\Models\Leadcenter;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LeadcenterImportCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1200;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($leadcenter_csv)
    {
        $this->leadcenter_csv = $leadcenter_csv;
    }
    public $leadcenter_csv;


    /**
     * Execute the job.
     *
     * @return void
     */
    // public function handle()
    // {
    //     try {
    //         Log::info("Started LeadcenterImportCSV");
    //         $filePath = $this->leadcenter_csv;

    //         $file = fopen(storage_path("app/{$filePath}"), 'r');
    //         $data = [];
    //         while (($row = fgetcsv($file)) !== false) {
    //             $data[] = $row;
    //         }
    //         fclose($file);
    //         // return $dataArray;
    //         $headers = array_shift($data);
    //         // dd(gettype($headers));
    //         $headers = preg_replace('/^[\x{FEFF}]+/u', '', $headers);
    //         $headers = str_replace("\t", '', $headers);
    //         $headers = array_map('strtolower', $headers);

    //         echo "Header Count = " . count($headers);
    //         $data = array_map(function ($row) use ($headers) {

    //             echo "Row Count = " . count($row);
    //             echo "\n";
    //             echo "existing identifier = " . $row[0];
    //             echo "\n";
    //             return array_combine($headers, $row);
    //         }, $data);

    //         foreach ($data as $key => $row) {
    //             Leadcenter::create([
    //                 'first_name'  => $row['first name'] ?? null,
    //                 'middle_name' => $row['middle initial'] ?? null,
    //                 'surname'     => $row['surname'] ?? null,
    //                 'gen_code'    => $row['gen code'] ?? null,
    //                 'street'      => $row['street'] ?? null,
    //                 'city'        => $row['city'] ?? null,
    //                 'zip'         => $row['zip code'] ?? null,
    //                 'state_abbr'  => $row['state'] ?? null,
    //                 'ssn'         => $row['ssn'] ?? null,
    //                 'score'       => $row['score'] ?? null,
    //                 'age'         => $row['age'] ?? null,
    //                 'no_of_oc'    => $row['no of oc'] ?? null,
    //                 'no_of_ac'    => $row['no of ac'] ?? null,
    //                 'td'          => $row['td'] ?? null,
    //                 'ta'          => $row['ta'] ?? null,
    //                 'd_to_ir'     => $row['d to ir'] ?? null,
    //                 'email'       => $row['email'] ?? null,
    //                 'phone'       => $row['phone'] != null ? json_encode(explode(',', preg_replace('/[^0-9,]+/', '', $row['phone']))) : null,
    //                 'is_complete' => 0,
    //                 'is_rc'       => isset($row['existing identifier']) ? 1 : 0,
    //             ]);
    //         }



    //         Storage::delete($filePath);
    //         Log::info("Ended LeadcenterImportCSV");
    //         return;
    //     } catch (Exception $e) {
    //         Log::info("ERROR IN LeadcenterImportCSV");
    //     }
    // }








    public function handle()
{
    try {
        Log::info("Started LeadcenterImportCSV");
        $filePath = $this->leadcenter_csv;

        $file = fopen(storage_path("app/{$filePath}"), 'r');
        $data = [];
        while (($row = fgetcsv($file)) !== false) {
            $data[] = $row;
        }
        fclose($file);

        $headers = array_shift($data);
        $headers = preg_replace('/^[\x{FEFF}]+/u', '', $headers);  // Handle BOM for UTF-8 files
        $headers = str_replace("\t", '', $headers);  // Remove tab characters
        $headers = array_map('strtolower', $headers);  // Convert headers to lowercase

        foreach ($data as $key => $row) {
            // Check if the number of headers and the number of columns in the row match
            if (count($headers) !== count($row)) {
                Log::warning("Skipping row {$key}: Mismatch between header count and row count. Header count: " . count($headers) . ", Row count: " . count($row));
                continue;  // Skip to the next iteration
            }

            // If the row and headers match, process the row
            $rowData = array_combine($headers, $row);  // Combine headers and row data

            Leadcenter::create([
                'first_name'  => $rowData['first name'] ?? null,
                'middle_name' => $rowData['middle initial'] ?? null,
                'surname'     => $rowData['surname'] ?? null,
                'gen_code'    => $rowData['gen code'] ?? null,
                'street'      => $rowData['street'] ?? null,
                'city'        => $rowData['city'] ?? null,
                'zip'         => $rowData['zip code'] ?? null,
                'state_abbr'  => $rowData['state'] ?? null,
                'ssn'         => $rowData['ssn'] ?? null,
                'score'       => $rowData['score'] ?? null,
                'age'         => $rowData['age'] ?? null,
                'no_of_oc'    => $rowData['no of oc'] ?? null,
                'no_of_ac'    => $rowData['no of ac'] ?? null,
                'td'          => $rowData['td'] ?? null,
                'ta'          => $rowData['ta'] ?? null,
                'd_to_ir'     => $rowData['d to ir'] ?? null,
                'email'       => $rowData['email'] ?? null,
                'phone'       => $rowData['phone'] != null ? json_encode(explode(',', preg_replace('/[^0-9,]+/', '', $rowData['phone']))) : null,
                'is_complete' => 0,
                'is_rc'       => isset($rowData['existing identifier']) ? 1 : 0,
            ]);
        }

        Storage::delete($filePath);  // Delete the CSV file after processing
        Log::info("Ended LeadcenterImportCSV");
    } catch (Exception $e) {
        Log::error("ERROR IN LeadcenterImportCSV: " . $e->getMessage());
    }
}






}
