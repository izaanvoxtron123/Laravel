<?php

namespace App\Http\Common;

use App\Http\Common\EmailHelper;
use App\Activity\Activity;
use App\Models\{ApiToken, Setting, User};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
/// Usage

// $doctors = '[
//     {"id": 10,"title": "Dr. Ameer Ali","lastCall": "7/29/2022 06:29pm","appointmentId": 230},
//     {"id": 10,"title": "Dr. Ameer Ali","lastCall": "7/29/2022 06:30pm","appointmentId": 231}, 
//     {"id": 10,"title": "Dr. Babar Ali","lastCall": "7/29/2022 12:58pm","appointmentId": ""},
//     {"id": 10,"title": "Dr. Available Ali","lastCall": "7/29/2022 12:58pm","appointmentId": ""}
// ]';
// $appointments = '[
//     {"id":299,"name":"Abid Khan","joinCallAt":"7/29/2022 12:57pm"},
//     {"id":232,"name":"Hamid Khan","joinCallAt":"7/29/2022 12:59pm"},
//     {"id":242,"name":"Hamid Ali","joinCallAt":"7/29/2022 6:33pm"}
// ]';


// $queueManagement = new Queue($doctors,$appointments);
// echo "<pre>";
// print_r($queueManagement->distribute());





/// Class
class Queue {
    private $doctor ;
    private $appointments;
    private $waitingTime;
   
    public function __construct($doctor, $appointments,$waitingTime=10)
    {
        $this->doctor = json_decode($doctor,true);
        $this->appointments = json_decode($appointments,true);
        $this->waitingTime = $waitingTime;
    }
   
    public function distribute()
    {
        
        // Get available Doctors
        $availableDoctorIndex = $this->getAvailableDoctors();

        // If found assign doctors
        if($availableDoctorIndex >= 0)
        {
            // Assign Doctor
            $assigned_doctors = $this->assignDoctor($availableDoctorIndex);
        }

        // Calculate Appointment Time
        $this->calculateWaitingTime();

        // Update waiting time
        $this->updateWaitingTime();


        return [
            'doctor' => $this->doctor,
            'assigned_doctors' => $assigned_doctors,
            'appointments' => $this->appointments,
        ];
    }


    private function getAvailableDoctors()
    {
        
        if(count($this->doctor) < 0)
        return [];

        $result = [];
        for($i=0; $i < count($this->doctor); $i++)
        {
            $doctor = $this->doctor[$i];
            $this->elapsedMins($i); // Calculate Elaspsed Minutes & Save
            if(!isset($doctor['appointmentId']) or empty($doctor['appointmentId']) )
            { $result[] = $i; }
        }
        return $result;
    }

    private function assignDoctor($doctorIndex)
    {
        $assigned_doctor = [];
        // Assign doctor to first appointment
        if($doctorIndex != null && count($doctorIndex) > 0)
        {
            for($i=0; $i < count($doctorIndex); $i++)
            {
                if(count($this->appointments) > 0)
                {
                    $this->doctor[$doctorIndex[$i]]["appointmentId"] = $this->appointments[0]["id"];
                    $this->doctor[$doctorIndex[$i]]["lastCall"] = Date("m/d/Y h:ia");
                    $assigned_doctor[] = $this->doctor[$doctorIndex[$i]];
                    $this->elapsedMins($i); // Calculate Elaspsed Minutes & Save
                    array_shift($this->appointments); // Remove the first index
                }
            }
            
            return $assigned_doctor;
        }else{
            return false; 
        }
        return $assigned_doctor;
    }

    private function calculateWaitingTime()
    {
        for($i=0; $i < count($this->doctor); $i++)
        {
            $this->elapsedMins($i); // Calculate Elaspsed Minutes & Save
        }
    }

    private function elapsedMins($doctorIndex)
    {
        $elapsedtime = time() - strtotime( $this->doctor[$doctorIndex]['lastCall']);
        $this->doctor[$doctorIndex]["elapsedMins"] = floor($elapsedtime / 60);
        return floor($elapsedtime / 60);
    }

    private function updateWaitingTime()
    {
        if(count($this->appointments) < 0 )
        return false;

        $waitingSlab = 0;

        for($i=0; $i < count($this->appointments); $i++)
        {
            if(isset($this->doctor[$i])){
                $standTime              = $this->doctor[$i]['elapsedMins'] > $this->waitingTime ? floor($this->waitingTime * 1.5) : $this->waitingTime;
                $waitingEstimateRow     = $standTime - $this->doctor[$i]['elapsedMins'];
                $waitingEstimate        = $waitingEstimateRow > 0 ? $waitingEstimateRow : -1;
                $this->appointments[$i]['estimatedSeconds'] = $waitingEstimate * 60;
            }else{
                if(count($this->doctor) > 0 && ($i % count($this->doctor)) == 0)
                $waitingSlab++;
                $waitingEstimate        = $waitingSlab * $this->waitingTime;
                $this->appointments[$i]['estimatedSeconds'] = $waitingEstimate * 60;
            }
        }
    }
}
