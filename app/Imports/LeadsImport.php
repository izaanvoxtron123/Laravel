<?php

namespace App\Imports;

use App\Http\Common\Helper;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class LeadsImport implements ToModel, WithHeadingRow, WithBatchInserts, SkipsOnFailure, WithValidation, WithChunkReading, ShouldQueue
{
    use SkipsFailures;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Customer([
            'first_name'  => $row['first_name'],
            'last_name' => $row['last_name'],
            'phone'    => $row['phone'],
            'email'    => $row['email'],
            'ssn'    => $row['ssn'],
            'dob'    => $row['dob'],
            'house_number'    => $row['house_number'],
            'quadrant'    => $row['quadrant'],
            'street_name'    => $row['street_name'],
            'street_type'    => $row['street_type'],
            'city'    => $row['city'],
            'state'    => $row['state'],
            'zip'    => $row['zip'],

        ]);
    }

    public function rules(): array
    {
        // return [];
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'ssn' => 'required',
            'house_number' => 'required',
            'street_name' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    
    public function chunkSize(): int
    {
        return 10000;
    }
}
