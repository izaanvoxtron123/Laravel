<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class UsersImport implements ToModel, WithHeadingRow, WithBatchInserts, SkipsOnFailure, WithValidation
{
    use SkipsFailures;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        return new User([
            'first_name'  => $row['first_name'] ?? null,
            'middle_name' => $row['middle_initial'] ?? null,
            'surname'    => $row['surname'] ?? null,
            'gen_code'    => $row['gen_code'] ?? null,
            'street'    => $row['street'] ?? null,
            'city'    => $row['city'] ?? null,
            'zip'    => $row['zip_code'] ?? null,
            'state_abbr'    => $row['state'] ?? null,
            'ssn'    => $row['ssn'] ?? null,
            'score'    => $row['score'] ?? null,
            'age'    => $row['age'] ?? null,
            'no_of_oc'    => $row['no_of_oc'] ?? null,
            'no_of_ac'    => $row['no_of_ac'] ?? null,
            'td'    => $row['td'] ?? null,
            'ta'    => $row['ta'] ?? null,
            'd_to_ir'    => $row['d_to_ir'] ?? null,
            'email'    => $row['email'] ?? null,

            'phone'    => $row['phone'] != null ? json_encode(explode(',', preg_replace('/[^0-9,]+/', '', $row['phone']))) : null,

        ]);
    }
    
    public function rules(): array
    {
        return [];
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
