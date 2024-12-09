<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Artwork as MainModel;
use App\Http\Common\Helper;
use Illuminate\Support\Facades\Validator;
use Auth;

class ArtworkController extends Controller
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
                "element_type" => "input",
                "input_type" => "number",
                "label" => "Amount",
                "name" => "amount",
                "placeholder" => "Enter Amount",
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ["required" => "required"],
            ],
            [
                "label" => "Description",
                "element_type" => "textarea",
                "name" => "description",
                "editor" => 0,
                "rows" => 5,
                "placeholder" => "Please Enter Description",
                "additional_ids" => ["id_1", "id_2"],
                "additional_classes" => ["class_1", "class_2"],
                "html_params" => ["required" => "required"],
            ],
            [
                "element_type" => "image",
                "label" => "Artwoork",
                "name" => "source",
                "include_asset_function" => 0,
                "additional_ids" => [],
                "additional_classes" => [],
                "html_params" => ['accept' => '.png,.jpg,.gif'],
            ],
        ];

        $this->input_elements = $input_elements;
    }

    public $folder_name = 'artwork'; // For view routes and file calling and saving
    public $module_name = 'Artwork'; // For toast And page header
    public $input_elements;
    
    public function view(Request $request){
        if($request->isMethod('post')){
            foreach($request->sequence as $key => $id){
                $sequence = $key + 1;
                MainModel::where('id', $id)->update(['sequence' => $sequence]);
            }
            return back();
        }else{
            $data['folder_name'] = $this->folder_name;
            $data['module_name'] = $this->module_name;
            $data['result'] = MainModel::where('user_id', Auth::user()->id)->get();
            $file = "admin.".$this->folder_name.".view";
            return view($file,$data);
        }
    }

    public function form(Request $request, $id = null){
        $request->validate(MainModel::getValidationRules($id));
        // $sequence = MainModel::count();
        // $sequence = $sequence + 1;
        $data = [
            'user_id' => Auth::user()->id,
            'name' => $request->name,
            'amount' => $request->amount,
            'description' => $request->description,
            'status' => $request->status,
        ];
        if($request->hasFile('source')){
            $data['source'] = $request->source->store($this->folder_name, Helper::STATIC_ASSET_DISK);
        }
        if($id){
            if(MainModel::where('id', $id)->update($data)){
                Helper::toast('success',$this->module_name.' Updated.');
            }
        }else{
            if(MainModel::create($data)){
                Helper::toast('success',$this->module_name.' created.');
            }
        }
        return redirect()->route($this->folder_name.'-view');
    }

    public function add(Request $request){
        if($request->isMethod('post')){
            return $this->form($request);
        }else{
            $data['page_header'] = "Add ".$this->module_name;
            $data['result'] = null;
            $input_elements = $this->input_elements;
            $data['include_status_radio'] = 1   ;
            return view('general_crud.general_view',$data)->with(compact('input_elements'));
        }
    }

    public function edit(Request $request, $id){
        $id = decrypt($id);
        if($request->isMethod('post')){
            return $this->form($request,$id);
        }else{
            $data['page_header'] = "Edit ".$this->module_name;
            $data['result'] = MainModel::find($id);
            $data['include_status_radio'] = 1;
            $input_elements = $this->input_elements;
            return view('general_crud.general_view',$data)->with(compact('input_elements'));
        }
    }

}
