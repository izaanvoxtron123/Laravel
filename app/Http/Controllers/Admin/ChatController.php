<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class ChatController extends Controller
{
    function redirect(Request $request)
    {
        $user = auth()->user();

        // Send a POST request to the external URL
        $response = Http::post('https://mern-chat-app-cpl3.onrender.com/api/auth/login', [
            'fullName' => $user->name,
            'username' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role->name,
        ]);

        return $response;
        // Optionally, you can handle the response here
        if ($response->successful()) {
            // Handle successful response (if needed)
            return response()->json(['message' => 'User redirected successfully']);
        } else {
            // Handle error
            return response()->json(['error' => 'Failed to redirect'], 500);
        }
    }
}
