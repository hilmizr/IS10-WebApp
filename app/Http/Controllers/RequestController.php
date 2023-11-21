<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ParagonIE\Halite\Asymmetric\EncryptionPublicKey;
use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\Halite\EncryptionKeyPair;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\HiddenString\HiddenString;
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

        $public_key = Storage::get('keys/' . Auth::user()->username . '.pub');
        $private_key = Storage::get('keys/' . Auth::user()->username . '.key');
        
        $hidden_public_key = new HiddenString($public_key);
        $hidden_private_key = new HiddenString($private_key);

        $public_key = new EncryptionPublicKey($hidden_public_key);
        $private_key = new EncryptionSecretKey($hidden_private_key);

        // $rsa = RSA::loadPrivateKey($private_key);
        foreach($messages as $message){
            $encrypted = \ParagonIE\Halite\Asymmetric\Crypto::decrypt(
                $message->encrypted_message,
                $private_key,
                $public_key
            );
            $message->encrypted_message = $encrypted->getString();
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

        $public_key = Storage::get('keys/' . Auth::user()->username . '.pub');
        $private_key = Storage::get('keys/' . Auth::user()->username . '.key');
        
        $hidden_public_key = new HiddenString($public_key);
        $hidden_private_key = new HiddenString($private_key);

        $public_key = new EncryptionPublicKey($hidden_public_key);
        $private_key = new EncryptionSecretKey($hidden_private_key);
        $keyPair = new EncryptionKeyPair($public_key, $private_key);
        // dd($messages);

        foreach ($messages as $message) {
            $decrypted = \ParagonIE\Halite\Asymmetric\Crypto::decrypt(
                $message->encrypted_message,
                $keyPair->getSecretKey(),
                $keyPair->getPublicKey()
            );
            $message->encrypted_message = $decrypted->getString();
        }

        return view('request.index',[
            'messages' => $messages
        ]);
    }

    public function send(){
        
    }
}
