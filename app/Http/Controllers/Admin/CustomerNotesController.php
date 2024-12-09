<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerNotes as MainModel;
use App\Http\Common\Helper;
use Auth;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class CustomerNotesController extends Controller
{
    function add(Request $request, $customer_id)
    {
        try{
            MainModel::create([
                'customer_id' => $customer_id,
                'author_id' => Auth::user()->id,
                'note' => $request->note
            ]);
            return [
                'status' => true,
                'message' => "Success"
            ];
        }catch(Exception $e){
            return [
                'status' => false,
                'message' => "Something went wrong"
            ];
        }
    }

    function get(Request $request, $customer_id)
    {
        try{
            $notes = MainModel::where('customer_id', $customer_id)->with('author')->orderBy('id','desc')->get();
            return [
                'status' => true,
                'data' => $notes,
                'message' => "Success"
            ];
        }catch(Exception $e){
            return [
                'status' => false,
                'message' => "Something went wrong"
            ];
        }

    }
}
