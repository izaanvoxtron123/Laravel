<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhitelistedIps as MainModel;
use App\Http\Common\Helper;
use Illuminate\Support\Facades\Validator;
use Auth;

class WhitelistedIpsController extends Controller
{

    public function __construct(array $attributes = array())
    {

        $input_elements = [
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Identifier",
                "name" => "identifier",
                "placeholder" => "Enter Ip Address Identifier",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "input",
                "input_type" => "text",
                "label" => "Ip Address",
                "name" => "ip",
                "placeholder" => "Enter Ip Address to Whitelist ",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],

            
            [
                "label" => "For Primary Office",
                "element_type" => "radio",
                "name" => "is_primary",
                "html_params" => ["required" => "required"],
                "buttons" => [
                    [
                        "value_element" => "text",
                        "label" => "Yes",
                        "value" => "1",
                        "checked_on_null" => 0,
                        "additional_ids" => [],
                        "additional_classes" => [],
                    ],
                    [
                        "value_element" => "text",
                        "label" => "No",
                        "value" => "0",
                        "checked_on_null" => 1,
                        "additional_ids" => [],
                        "additional_classes" => [],
                    ],
                ],
            ],
        ];

        $this->input_elements = $input_elements;
    }

    public $folder_name = 'whitelisted_ips'; // For view routes and file calling and saving
    public $module_name = 'Whitelisted Ips'; // For toast And page header
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
            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;
            $data['result'] = MainModel::all();
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
            'identifier' => $request->identifier,
            'ip' => $request->ip,
            'is_primary' => $request->is_primary,
            'status' => $request->status,
        ];
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

    public function delete(Request $request, $id)
    {
        $id = decrypt($id);
        if (MainModel::where('id',$id)->delete()) {
            Helper::toast('success',  'IP Deleted.');
        } else {
            Helper::toast('error', 'Something went wrong');
        }
        return back();
    }
}
