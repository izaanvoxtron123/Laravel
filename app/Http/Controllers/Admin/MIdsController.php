<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MIds as MainModel;
use App\Models\Report;
use App\Http\Common\Helper;
use App\Models\Customer;
use App\Models\User;
use Auth;

class MIdsController extends Controller
{
    public function __construct(array $attributes = array())
    {
        $input_elements = [
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Name",
                "name" => "name",
                "placeholder" => "Enter Name",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],


            [
                "label" => "Criteria",
                "element_type" => "textarea",
                "name" => "criteria",
                "editor" => 1,
                "rows" => 3,
                "placeholder" => "Please Criteria",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => [],
            ],

        ];

        $this->input_elements = $input_elements;
    }

    public $folder_name = 'm_ids'; // For view routes and file calling and saving
    public $module_name = 'M Id'; // For toast And page header
    public $input_elements;

    public function view(Request $request)
    {
        if ($request->isMethod('post')) {
            foreach ($request->sequence as $key => $id) {
                $sequence = $key + 1;
                MainModel::where('id', $id)->update(['sequence' => $sequence]);
            }
            return back();
        } else {
            $result = MainModel::all();

            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;
            $data['result'] = $result;
            $file = "admin." . $this->folder_name . ".view";
            return view($file, $data);
        }
    }

    public function form(Request $request, $id = null)
    {
        $request->validate(MainModel::getValidationRules($id));
        // $sequence = MainModel::count();
        // $sequence = $sequence + 1;
        $data = [
            'name' => $request->name,
            'criteria' => $request->criteria,
            'status' => $request->status,
        ];
        if ($request->customer_id) {
            $data['customer_id'] = $request->customer_id;
        }
        if ($request->hasFile('source')) {
            $data['source'] = $request->source->store($this->folder_name, Helper::STATIC_ASSET_DISK);
        }
        if ($id) {
            if (MainModel::where('id', $id)->update($data)) {
                Helper::toast('success', $this->module_name . ' Updated.');
            }
        } else {
            if (MainModel::create($data)) {
                Helper::toast('success', $this->module_name . ' created.');
            }
        }
        return redirect()->route($this->folder_name . '-view');
    }

    public function add(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->form($request);
        } else {
            $data['page_header'] = "Add " . $this->module_name;
            $data['result'] = null;
            $input_elements = $this->input_elements;
            $data['include_status_radio'] = 1;
            return view('general_crud.general_view', $data)->with(compact('input_elements'));
        }
    }

    public function edit(Request $request, $id)
    {
        $id = decrypt($id);
        if ($request->isMethod('post')) {
            return $this->form($request, $id);
        } else {
            $data['page_header'] = "Edit " . $this->module_name;
            $data['result'] = MainModel::find($id);
            $data['include_status_radio'] = 1;
            $input_elements = $this->input_elements;
            return view('general_crud.general_view', $data)->with(compact('input_elements'));
        }
    }

    function get()
    {
        try {
            $user = Auth::user();
            if (isset($user->office) && isset($user->office->mids)) {
                $m_ids = $user->office->mids;
            } else {
                $m_ids = [];
            }
            // $m_ids = MainModel::where('status' , true)->get();
            return [
                "status" => true,
                "message" => "success",
                "data" => $m_ids,
            ];
        } catch (\Exception $e) {
            return [
                "status" => false,
                "message" => "Something went wrong " . $e->getMessage(),
                "data" => null,
            ];
        }
    }

    function delete(Request $request, $id)
    {
        try {
            MainModel::where('id', $id)->delete();
            Helper::toast('success', $this->module_name . ' Deleted.');
            return back();
        } catch (\Exception $e) {
            Helper::toast('error', 'Something went wrong. ' . $e->getMessage());
            return back();
        }
    }
}
