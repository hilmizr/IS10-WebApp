<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RequestMessage;

class RequestMessageController extends Controller
{
    public function sendRequest(Request $request)
    {
        $validated = $request->validate([
            'destination_id' => 'required|exists:users,id',
            'source_id' => 'required|exists:company_users,id',
            'encrypted_message' => 'required|string',
        ]);

        $message = RequestMessage::create($validated);

        if ($message) {
            return response()->json(['message' => 'Request sent successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to send request'], 500);
        }
    }
}
