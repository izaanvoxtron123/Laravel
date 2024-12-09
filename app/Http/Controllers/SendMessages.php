<?php

namespace App\Http\Controllers;

use App\Events\{SendMessage};
use App\Models\{Message, User, Chat};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SendMessages extends Controller
{
    public function search(Request $request){
        try{
            $searchQuery = $request->input('query');
    
            $lists = Chat::whereHas('user_1', function ($query) use ($searchQuery) {
            $query->where('name', '=', $searchQuery);
                })
                ->orWhereHas('user_2', function ($query) use ($searchQuery) {
                    $query->where('name', '=', $searchQuery);
                })
                ->groupBy('user_1', 'user_2') // Assuming these are the foreign keys in your Chat model
                ->with(['user_1', 'user_2'])
                ->get();
            return response()->json([
                'success' => true,
                'lists' => $lists
                ]); 
        }catch(\Exception $e){
            return response()->json([
              'success' => false,
              'message' => $e->getMessage()
                ]);
        }
    }
    public function newMessage($id){
        $data = Message::where([['from_id',auth()->user()->id],['to_id',$id]])->where([['to_id',auth()->user()->id],['from_id',$id]])->latest()->first();
        dd($data);
    }
    public function chat()
    {
        $id = Auth::user()->id;
        $update = User::find(auth()->user()->id)->update([
            'is_online' => 1
        ]);
        $user = User::findOrfail($id);

        $room = Chat::where([
            ['user_1', auth()->user()->id],
            ['user_2', $id]

        ])->orWhere([
                    ['user_1', $id],
                    ['user_2', auth()->user()->id]
                ])->first();
        if ($room == null) {
            $room = Chat::create([
                'user_1' => auth()->user()->id,
                'user_2' => $id
            ]);
        }
        return view('admin.chat.view', [
            'user' => $user,
            'room_id' => $room->id,
            'messages' => $room->messages
        ]);
    }
    public function list($id)
    {
        $update = User::find(auth()->user()->id)->update([
            'is_online' => 1
        ]);
        try {
            $user = User::findOrfail($id);
            $lists = Chat::where('user_1', $id)->orWhere('user_2', $id)->groupBy('user_1', 'user_2')->with('user_1','user_2')->get();
            if ($lists == null) {
                dd($id);
                $room = Chat::create([
                    'user_1' => auth()->user()->id,
                    'user_2' => $id
                ]);
            }
            return [
                'success' => true,
                'user' => $user,
                'lists' => $lists,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    public function adminMyList(){
        try{
            $id = Auth::user()->id;
            $user = User::findOrfail($id);
            $lists = Chat::where('user_1', $id)->orWhere('user_2', $id)->groupBy('user_1', 'user_2')->with('user_1','user_2')->get();
            return [
                'success' => true,
                'user' => $user,
                'lists' => $lists,
            ];
        }
        catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    public function allChatList(){
        try{
            if(Auth::user()->role_id == 1){
                $list = Chat::where('user_1','!=','user_2')->where([['user_1','!=',auth()->user()->id],['user_2','!=',auth()->user()->id]])
                ->orWhere([['user_2','!=',auth()->user()->id],['user_1','!=',auth()->user()->id]])
                ->with('user_1','user_2')->groupBy('user_1','user_2')->get();
            }
            return [
                'success'=>true,
                'list'=>$list,
            ];
        }
        catch(\Exception $e){
            return [
                'success'=>false,
                'error'=>$e->getMessage(),
            ];
        }
    }
    // public function getList($user_1, $user_2)
    // {
    //     $list = Chat::where('user_1', $user_1)->orWhere('user_2', $user_2)->groupBy('user_1', 'user_2')->with('user_1', 'user_2')->orderedBy('messages','asc')->get();
    //     return $list;
    // }
    public function getAllChatMessages($user_1, $user_2)
    {
        $user = User::find($user_1);
        $room = Message::where([['from_id', $user_1],['to_id', $user_2]])->orWhere([['from_id',$user_2],['to_id',$user_1]])->orderBy('created_at')->get();
        // dd($room);
        return [
            'success' => true,
            'user'=>$user,
            'messages' => $room,
        ];   
    }
    public function getChat($id)
    {
        $user = User::findOrfail($id);
        $room = Message::where('from_id', auth()->user()->id)->orWhere('to_id', auth()->user()->id)->first();
        $chats = Message::where('chat_id', $room->id)->get();
        if ($room == null) {
            dd($id);
            $room = Chat::create([
                'user_1' => auth()->user()->id,
                'user_2' => $id
            ]);
        }
        $data = [
            'room_id' => $room,
            'messages' => $room->messages,
            'chats' => $chats,
        ];

        return response()->json($data);
    }
    public function newChat($id)
    {
        try {
            $user = User::findOrfail($id);
            $room = Message::where([['from_id', auth()->user()->id],['to_id', $id]])->orWhere([['from_id',$id],['to_id',auth()->user()->id]])->orderBy('created_at')->get();
            $chat = Chat::where([['user_1', auth()->user()->id],['user_2', $id]])->orWhere([['user_1',$id],['user_2',auth()->user()->id]])->first();
            if ($chat == null) {
                $chat = Chat::create([
                    'user_1' => auth()->user()->id,
                    'user_2' => $id
                ]);
            }
            return [
                'success' => true,
                'id'=>$id,
                'user' => $user,
                'messages' => $room,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

    }
    function getUsers()
    {
        $auth_user = Auth::user();
        if($auth_user->role_id == 1){
            $users = User::where('id', '!=', $auth_user->id)->where('status', 1)->get();
        }
        if($auth_user->role_id == 2){
            $users = User::where('id', '!=', $auth_user->id)->where('role_id',3)->where('status', 1)->get();
        }
        if($auth_user->role_id == 3){
            $users = User::where('id', '!=', $auth_user->id)->where('role_id',3)->where('status', 1)->get();
        }
        return $users;
    }
    public function sendMessage(Request $request)
    {
        try{
            $fromId = auth()->user()->id;
            $toUserId = $request->touserId;
            $message = $request->message;
            $status = $request->status;
            $user = auth()->user()->name;
            $id = $request->roomid;
            $file = null;
            
            if($request->hasFile('file')){
                $validatedData = $request->validate([
                    'file' => 'nullable|mimes:pdf,jpg,jpeg,png,gif,mp4,mov,avi,xls,csv',
                ]);
                $file = $request->file('file')->store('chats', 'public');
            }
            $save_message = Message::create([
                'message' => $message,
                'from_id' => $fromId,
                'to_id' => $toUserId,
                'chat_id' => $id,
                'is_readed' => $status,
                'file'=>$file
            ]);
            event(new SendMessage($message, $user, $id, $fromId, $status,$file));
            return response()->json(['success'=>true]);
        }
        catch(\Exception $e){
            return $e->getMessage();
        }
    }
    public function read_all_messages(Request $request)
    {
        $to_id = $request->toId;
        $room_id = $request->roomId;
        $update = Message::where([
            ['chat_id', $room_id],
            ['from_id', auth()->user()->id],
            ['to_id', $to_id],
            ['is_readed', 0],
        ])->update([
                    'is_readed' => 1
                ]);
        return null;
    }
}