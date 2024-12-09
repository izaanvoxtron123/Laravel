<?php

namespace App\Imports;

use App\Http\Common\Helper;
use App\Models\Leadcenter;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Illuminate\Validation\Rule;



class LeadcenterImport implements ToModel, WithHeadingRow, WithBatchInserts, SkipsOnFailure, WithValidation
{
    use SkipsFailures;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        return new Leadcenter([
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
}
