<?php

namespace App\Http\Common;

use App\Models\{MedicalRecordFile, MedicalRecord};

class MedicalRecordHelper {

    /**
     * Get the medical record object and return the reports name with its type count
     */
    public static function getTotalReports($medicalRecordId) {

        $medicalRecord = MedicalRecord::where('id', $medicalRecordId)
        ->withCount([
            'medicalRecordFiles as medicines_count' => function($query){
                $query->where('prescription_element_type_id', Constant::MEDICAL_FILE_TYPE_MEDICINE);
            },
            'medicalRecordFiles as lab_test_count' => function($query){
                $query->where('prescription_element_type_id', Constant::MEDICAL_FILE_TYPE_LAB_TEST);
            },
            'medicalRecordFiles as prescription_count' => function($query){
                $query->where('prescription_element_type_id', Constant::MEDICAL_FILE_TYPE_PRESCRIPTION);
            }
        ])->first();
        $response = "" ;
        if($medicalRecord && $medicalRecord->medicines_count){
            $response = $medicalRecord->medicines_count > 1 ? $medicalRecord->medicines_count . " Medicines" : $medicalRecord->medicines_count . " Medicine";
        }
        if($medicalRecord && $medicalRecord->lab_test_count){
            $response = $medicalRecord->lab_test_count > 1 ? $medicalRecord->lab_test_count . " Lab Tests" : $medicalRecord->lab_test_count . " Lab Test";
        }
        if($medicalRecord && $medicalRecord->prescription_count){
            $response = $medicalRecord->prescription_count > 1 ? $medicalRecord->prescription_count . " Prescriptions" : $medicalRecord->prescription_count . " Prescription";
        }
        return $response;
    }
}
