<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerPhones;
use App\Models\CustomerRecordings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncRecordings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync recordings from SFTP and save to local storage and database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->sftp = Storage::disk('sftp');
    }
    protected $sftp;
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $exclude_directories = CustomerRecordings::selectRaw('DATE(date) as unique_date')
            ->distinct()
            ->pluck('unique_date')
            ->toArray();

        $exclude_directories[] = date("Y-m-d");
        $exclude_directories = array_unique($exclude_directories);


        $exclude_directories = array_filter($exclude_directories, function ($date) {
            return !is_null($date);
        });

        $files = [];
        $directories = $this->sftp->directories();

        // Populate from database
        // $exclude_directories = [
        //     "2024-01-02",
        //     "2024-01-03",
        //     "2024-01-04",
        //     "2024-01-05",
        //     date("Y-m-d"),
        // ];

        $pending_directories = array_diff($directories, $exclude_directories);
        $working_directory = count($pending_directories) > 0 ? reset($pending_directories) : "";

        $directoryFiles = $this->sftp->files($working_directory);

        foreach ($directoryFiles as $file) {
            $customer_id = $this->getCustomerIdByFilename($file);
            if (!empty($customer_id)) {
                try {
                    $fileContent = $this->sftp->get($file);

                    // Ensure the directory exists
                    $directoryPath = 'customer_recordings/' . $working_directory;
                    if (!Storage::disk('public')->exists($directoryPath)) {
                        Storage::disk('public')->makeDirectory($directoryPath, 0755, true);
                    }

                    // Generate a random name for the file
                    $randomName = uniqid() . '-' . str_replace(' ', '-', pathinfo($file, PATHINFO_FILENAME)) . '.' . pathinfo($file, PATHINFO_EXTENSION);

                    // Save the file to the local storage with the random name
                    $saved_file_path = $directoryPath . '/' . $randomName;
                    $saved = Storage::disk('public')->put($saved_file_path, $fileContent);

                    if ($saved) {
                        Log::info("File saved: " . $saved_file_path);

                        CustomerRecordings::create([
                            "customer_id" => $customer_id,
                            "date" => $working_directory,
                            "source" => $saved_file_path,
                        ]);
                    } else {
                        Log::error("Failed to save file: " . $saved_file_path);
                    }
                } catch (\Exception $e) {
                    Log::error("Error saving file: " . $e->getMessage());
                }
            }
            // Save files against customer_id in database
            $files[] = $file;
        }

        // Save directory name in database to populate exclude_directories
        return 1;
    }

    private function getCustomerIdByFilename($filename)
    {
        preg_match_all('/\d{7,15}/', $filename, $matches);
        $phoneNumbers = [];
        foreach ($matches[0] as $number) {
            if (strlen($number) >= 10) {
                $phoneNumbers[] = substr($number, -10);
            }
        }
        $phoneNumbers = array_filter($phoneNumbers, function ($number) {
            return strlen($number) === 10;
        });
        if (!empty($phoneNumbers)) {
            array_shift($phoneNumbers);
        }

        if (count($phoneNumbers)) {
            $customer = Customer::where(function ($query) use ($phoneNumbers) {
                foreach ($phoneNumbers as $number) {
                    $query->orWhere('phone', 'LIKE', "%{$number}%")
                        ->orWhere('secondary_phones', 'LIKE', "%{$number}%");
                }
            })->first();

            // If a customer is found in the `customers` table
            if ($customer) {
                return $customer->id;
            }

            // Search for the customer ID in the `customer_phones` table
            $customerPhone = CustomerPhones::whereIn('phone_number', $phoneNumbers)
                ->first();

            // If a customer is found in the `customer_phones` table
            if ($customerPhone) {
                return $customerPhone->customer_id;
            }
        }

        // Return null if no customer is found
        return null;
    }
}
