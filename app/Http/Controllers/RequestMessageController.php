<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RequestMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

class RequestMessageController extends Controller
{
    public function sendRequest(Request $request)
    {
        if ($request->type == 'company') {
            $destination_username = DB::table('company_users')
                ->where('id', '=', $request->destination_id)
                ->value('username');
            $public_key = Storage::get('keys/' . $destination_username . '.pub');
            // $public_key = DB::table('company_users')
            //     ->join('key_clients', 'key_clients.name', '=', 'company_users.username')
            //     ->join('key_api_credentials', 'key_api_credentials.key_client_id', '=', 'key_clients.id')
            //     ->where('company_users.id', '=', $request->destination_id)
            //     ->value('key_api_credentials.public_key');
            $rsa = RSA::loadPublicKey($public_key);
            $encrypted = $rsa->encrypt($request->message);
            // $request->encrypted_message = base64_encode($encrypted);
        }
        else{
            $destination_username = DB::table('users')
                ->where('id', '=', $request->destination_id)
                ->value('username');
            $public_key = Storage::get('keys/' . $destination_username . '.pub');
            // $public_key = DB::table('users')
            //     ->join('key_clients', 'key_clients.name', '=', 'users.username')
            //     ->join('key_api_credentials', 'key_api_credentials.key_client_id', '=', 'key_clients.id')
            //     ->where('users.id', '=', $request->destination_id)
            //     ->value('key_api_credentials.public_key');
            $rsa = RSA::loadPublicKey($public_key);
            $encrypted = $rsa->encrypt($request->message);
            $private_key = Storage::get('keys/' . $destination_username . '.pem');
            $rsa = RSA::loadPrivateKey($private_key);
            $decrypted = $rsa->decrypt($encrypted);
            // $request->encrypted_message = base64_encode($encrypted);
        }
        $validated = $request->validate([
            'destination_id' => 'required',
            'source_id' => 'required',
            'encrypted_message' => 'required|string',
            'type' => 'required|string',
        ]);
        // $encrypted = base64_encode($encrypted);
        $validated['encrypted_message'] = $decrypted;

        $message = RequestMessage::create($validated);

        if ($message) {
            return response()->json(['message' => 'Request sent successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to send request'], 500);
        }
    }

    

    public function download(Request $request)
    {
        
    }
}
