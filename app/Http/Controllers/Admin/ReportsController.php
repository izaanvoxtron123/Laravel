<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report as MainModel;
use App\Http\Common\Helper;
use App\Models\Customer;
use App\Models\CustomerLogs;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Storage;


class ReportsController extends Controller
{
    public function __construct(array $attributes = array())
    {
        // $input_elements = [
        //     [
        //         "label" => "Report Type",
        //         "element_type" => "dropdown",
        //         "name" => "type",
        //         "options" => [
        //             [
        //                 "type" => "Experian Credit Report",
        //             ],
        //             [
        //                 "type" => "Equifax Credit Report",
        //             ],
        //         ],
        //         "value_element" => "type",
        //         "label_element" => "type",
        //         "additional_ids" => [],
        //         "additional_classes" => [],
        //         "html_params" => ["required" => "required"],
        //     ],
        //     [
        //         "label" => "Customer",
        //         "element_type" => "dropdown",
        //         "name" => "customer_id",
        //         "options" => Customer::where('status', 1)->get(),
        //         "value_element" => "id",
        //         "label_element" => "first_name",
        //         "additional_ids" => [],
        //         "additional_classes" => [],
        //         "html_params" => ["required" => "required"],
        //     ],
        //     [
        //         "label" => "Manager",
        //         "element_type" => "dropdown",
        //         "name" => "manager_id",
        //         "options" => User::where(['status' => 1, 'role_id'  => User::MANAGER_ROLE])->get(),
        //         "value_element" => "id",
        //         "label_element" => "name",
        //         "additional_ids" => [],
        //         "additional_classes" => [],
        //         "html_params" => [],
        //     ],
        //     [
        //         "label" => "Priority",
        //         "element_type" => "dropdown",
        //         "name" => "priority",
        //         "options" => [
        //             [
        //                 "priority" => "default",
        //                 "label" => "Default",
        //             ],
        //             [
        //                 "priority" => "high",
        //                 "label" => "High",
        //             ],
        //         ],
        //         "value_element" => "priority",
        //         "label_element" => "label",
        //         "additional_ids" => [],
        //         "additional_classes" => [],
        //         "html_params" => ["required" => "required"],
        //     ],

        // ];

        // $this->input_elements = $input_elements;
        $this->report_base_url = env('CRS_BASE_URL', "https://api-sandbox.stitchcredit.com:443/api/");
    }

    public $folder_name = 'report'; // For view routes and file calling and saving
    public $module_name = 'Reports'; // For toast And page header
    public $input_elements;

    private $report_base_url = "";

    function get(Request $request, $customer_id)
    {
        try {
            $reports = MainModel::where([
                'customer_id' => $customer_id,
                'request_id' => null,
                'status' => 1,
            ])->orderBy('id', 'desc')->get(['id', 'customer_id', 'request_id', 'report_type', 'report_pdf', 'created_at']);

            return [
                "status" => true,
                "message" => "success",
                "data" => $reports
            ];
        } catch (\Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong",
                "data" => null
            ];
        }
    }

    function detail(Request $request, $id)
    {

        $id = decrypt($id);
        $report = MainModel::find($id);
        CustomerLogs::createLog($report->customer_id, CustomerLogs::REPORT_VIEWED);

        if ($report->report_pdf) {
            return redirect('storage/' . $report->report_pdf);
        }

        $score = null;
        $remarks = '';
        if (isset($report->report['data']['models'][0]['score'])) {
            $score = $report->report['data']['models'][0]['score'];
        }

        if ($score) {
            if ($score <= 600)
                $remarks = "Low";
            if ($score >= 601 && $score <= 700)
                $remarks = "Average";
            if ($score >= 701 && $score <= 800)
                $remarks = "Excellent";
            if ($score >= 801 && $score <= 900)
                $remarks = "Perfect";
        }

        $data['report'] = $report;
        $data['score'] = $score;
        $data['remarks'] = $remarks;
        $data['report_data'] = $report->report['data'] ?? [];
        $file = "admin." . $this->folder_name . ".detail";

        return view($file, $data);
        dd($report->report);
    }

    function fetch(Request $request, $customer_id)
    {
        $customer_id = decrypt($customer_id);
        if ($request->isMethod('post')) {

            $request->validate(MainModel::getValidationRules());
            $payload = $request->except(['_token', 'report_type']);
            $report_type = $request->report_type;
            $report = $this->fetch_equifax_report($report_type, $payload);
            $report_pdf = null;

            if ($report) {
                $decoded_report = json_decode($report);
                if ($decoded_report && isset($decoded_report->pdfReportId)) {
                    $report_pdf = $this->download_report($decoded_report->pdfReportId, $report_type);
                }
            }
            $response = MainModel::create([
                'customer_id' => $customer_id,
                'report_type' => $report_type == "vantage4" ? "Equifax Prequal Vantage 4" : "Equifax FICO 9",
                'report' => $report,
                'report_pdf' => $report_pdf,
            ]);
            if ($response) {

                CustomerLogs::createLog($customer_id, CustomerLogs::REPORT_FETCHED, null, $response->id);
                return redirect(route('report-detail', ['id' => $response->e_id]));
            } else {
                Helper::toast('error', 'Something went wrong');
                return back();
            }
        } else {
            $customer = Customer::find($customer_id);

            $data['customer'] = $customer;
            $file = "admin." . $this->folder_name . ".form";
            return view($file, $data);
        }
    }

    private function fetch_equifax_report($type, $payload)
    {
        $login = $this->login();
        $curl = curl_init();
        $base_url = $this->report_base_url;
        if ($type == "vantage4") {
            $endpoint = 'equifax/credit-report/efx-prequal-vantage4';
        } else {
            $endpoint = 'equifax/credit-report/efx-business-principal-fico9';
        }
        $url = $base_url . $endpoint;

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $login->token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    private function download_report($report_pdf_id, $type)
    {
        $login = $this->login();
        $curl = curl_init();
        $base_url = $this->report_base_url;
        if ($type == "vantage4") {
            $report_type = '/efx-prequal-vantage4';
        } else {
            $report_type = '/efx-business-principal-fico9';
        }
        $endpoint = 'equifax/custom-credit-report/' . $report_pdf_id . $report_type;
        $endpoint = 'equifax/pdf-credit-report/' . $report_pdf_id;

        $url = $base_url . $endpoint;

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
                'Accept: application/pdf',
                'Authorization: Bearer ' . $login->token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // dd($url, $response);

        // Specify the local file path to save the PDF
        if (!is_dir('storage/reports/')) {
            // dir doesn't exist, make it
            mkdir('storage/reports/');
        }
        $localFilePathWithoutStoragePath = 'reports/' . time() . ".pdf";
        $localFilePath = "storage/" . $localFilePathWithoutStoragePath;
        // Save the PDF content to the local file
        file_put_contents($localFilePath, $response);


        // Storage::put($localFilePath, $response);

        return $localFilePathWithoutStoragePath;
    }

    private function login()
    {
        $curl = curl_init();
        $username = env('CRS_USERNAME', 'daniel@pathly.io');
        $password = env('CRS_PASSWORD', 'W0eteBEZQ5NbUGkEzE');
        $payload = [
            "username" => $username,
            "password" => $password
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->report_base_url . 'users/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }
}
