<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RCLExport;
use App\Http\Common\Helper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerExports;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CustomerExportsController extends Controller
{

    function view()
    {
        $exports = CustomerExports::orderBy('id', 'desc')->get();
        return view('admin.customer.exports', ['result' => $exports]);
    }

    function download(Request $request, $export_id)
    {
        $export_id = decrypt($export_id);

        $export = CustomerExports::find($export_id);
        return Storage::disk('public')->download($export->path);
    }

    function rcl(Request $request)
    {
        if ($request->isMethod('post')) {

            try {
                $query = Customer::where('is_complete', 0)
                    ->where('in_rework', 0);

                if ($request->progress != '') {
                    $query = $query->whereIn('progress', $request->progress);
                }

                if ($request->start_date != '' && $request->end_date != '') {
                    $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
                } elseif ($request->start_date != '') {
                    $query->where('created_at', '>=', $request->start_date);
                } elseif ($request->end_date != '') {
                    $query->where('created_at', '<=', $request->end_date);
                }

                $customers = $query->select('id')->limit(10000)->pluck('id')->toArray();
                // $customers = $query->select('id')->pluck('id')->count();

                $filename = "RCL-batch-" . time() . "-" . Carbon::now()->format('m-d-Y_H-i_A') . ".csv";
                Excel::queue(new RCLExport($customers), 'public/customer_exports_as_rcl/' . $filename);

                CustomerExports::create([
                    'path' => 'customer_exports_as_rcl/' . $filename,
                    'filename' => $filename,
                ]);


                Helper::toast('success', 'Downloading Started. This will be visible shortly HERE. Total customer exported are ' . number_format(count($customers)) . ' Filename will be ' . $filename . '.');
                return redirect()->route('customer_exports-view');
            } catch (Exception $e) {
                return [
                    "status" => false,
                    "message" => "Something went wrong" . $e->getMessage(),
                    "data" => null
                ];
            }


            return $request->post();
        } else {
            $progress = Customer::getProgress();

            $data = [
                'progress' => $progress,
                'page_header' => 'RCL Exporter',
            ];
            return view('admin.customer.rcl_exporter', $data);
        }
    }
}
