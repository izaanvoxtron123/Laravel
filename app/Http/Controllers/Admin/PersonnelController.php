<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Personnel as MainModel;
use App\Models\User;
use App\Http\Common\Helper;
use App\Models\WhitelistedIps;
use Auth;

class PersonnelController extends Controller
{
    public function __construct(array $attributes = array())
    {
        $input_elements = [
            [
                "label" => "Type",
                "element_type" => "dropdown",
                "name" => "type",
                "options" => [
                    ["type" => MainModel::T_L_NAME],
                    // ["type" => MainModel::T_O_PERSON],
                    ["type" => MainModel::CLOSER_NAME],
                ],
                "value_element" => "type",
                "label_element" => "type",
                "select_element" => "type",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],

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

        ];
        
        $this->input_elements = $input_elements;
    }

    public $folder_name = 'personnel'; // For view routes and file calling and saving
    public $module_name = 'Personnel'; // For toast And page header
    public $input_elements;
    public $progress;

    public function view(Request $request)
    {
        if ($request->isMethod('post')) {
            foreach ($request->sequence as $key => $id) {
                $sequence = $key + 1;
                MainModel::where('id', $id)->update(['sequence' => $sequence]);
            }
            return back();
        } else {
            $agents = User::where([
                'role_id' => User::AGENT_ROLE
            ])->orderBy('name')->get();

            $data['result'] = MainModel::all();
            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;
            $file = "admin." . $this->folder_name . ".view";
            return view($file, $data);
        }
    }

    public function form(Request $request, $id = null)
    {
        // dd($request->post());
        $request->validate(MainModel::getValidationRules($id));
        // $sequence = MainModel::count();
        // $sequence = $sequence + 1;
        $data = [
            'name' => $request->name,
            'type' => $request->type,
            'status' => $request->status,
        ];
        if ($request->hasFile('source')) {
            $data['source'] = $request->source->store($this->folder_name, Helper::STATIC_ASSET_DISK);
        }
        if ($id) {
            // $current_state = MainModel::find($id);
            if (MainModel::where('id', $id)->update($data)) {
                Helper::toast('success', $this->module_name . ' Updated.');
            }
        } else {
            if ($created_data = MainModel::create($data)) {
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
}
