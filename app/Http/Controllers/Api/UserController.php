<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User};

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        try {
            $users = User::where('status', 1)
                ->when($request->filled('role_ids'), function ($query) use ($request) {
                    $query->whereIn('role_id', $request->role_ids);
                })
                ->get();
            if (!count($users)) {
                return $this->returnResponse(400, 'Users not found.');
            }
            $data = [
                'users' => $users
            ];
            return $this->returnResponse(200, 'Users fetched successfully', $data);
        } catch (\Exception $e) {
            return $this->returnResponse(500, $e->getMessage());
        }
    }
}
