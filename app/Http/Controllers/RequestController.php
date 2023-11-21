<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

class RequestController extends Controller
{
    //
    public function user_index(){
        $messages = DB::table('request_messages')
                        ->select('request_messages.*','users.username as to', 'company_users.username as from')
                        ->join('users', 'users.id', '=', 'request_messages.destination_id')
                        ->join('company_users', 'company_users.id', '=', 'request_messages.source_id')
                        ->where('request_messages.destination_id', '=', Auth::user()->id)
                        ->get();
        // $private_key = DB::table('users')
        //         ->join('key_clients', 'key_clients.name', '=', 'users.username')
        //         ->join('key_api_credentials', 'key_api_credentials.key_client_id', '=', 'key_clients.id')
        //         ->where('users.id', '=', Auth::user()->id)
        //         ->value('key_api_credentials.private_key');
        $private_key = Storage::get('keys/' . Auth::user()->username . '.pem');
        $rsa = RSA::loadPrivateKey($private_key);
        foreach($messages as $message){
            $message->encrypted_message = $rsa->decrypt($message->encrypted_message);
        }
        return view('request.user_index',[
            'messages' => $messages
        ]);
    }

    public function company_index(){
        $messages = DB::table('request_messages')
                        ->select('request_messages.*','company_users.username as to', 'users.username as from')
                        ->join('company_users', 'company_users.id', '=', 'request_messages.destination_id')
                        ->join('users', 'users.id', '=', 'request_messages.source_id')
                        ->where('request_messages.destination_id', '=', Auth::user()->id)
                        ->get();
        // dd($messages);
        return view('request.index',[
            'messages' => $messages
        ]);
    }

    public function send(){
        
    }
}
