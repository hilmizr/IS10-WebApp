<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use Illuminate\Http\Request;
use App\Models\RequestMessage;
use App\Models\SymmetricKey;
use App\Models\User;
use Defuse\Crypto\Key;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ParagonIE\Halite\Asymmetric\EncryptionPublicKey;
use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\Halite\EncryptionKeyPair;
use ParagonIE\HiddenString\HiddenString;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Spatie\Crypto\Rsa\PublicKey;

class RequestMessageController extends Controller
{
    public function sendRequest(Request $request)
    {
        $destination_username = DB::table($request->type)
            ->where('id', '=', $request->destination_id)
            ->value('username');

        $public_key = Storage::get('keys/' . $destination_username . '.pub');
        $private_key = Storage::get('keys/' . $destination_username . '.key');

        if($request->type == 'company_users'){
            $user_temp = CompanyUser::find($request->destination_id);
            $public_key = $this->DecryptAES($public_key, $user_temp->companyKey->key);
            $private_key = $this->DecryptAES($private_key, $user_temp->companyKey->key);
        }
        else{
            $user_temp = User::find($request->destination_id);
            $public_key = $this->DecryptAES($public_key, $user_temp->userKey->key);
            $private_key = $this->DecryptAES($private_key, $user_temp->userKey->key);
        }
        
        Log::debug($public_key);
        Log::debug($private_key);
        // Log::debug($destination_username);

        $hidden_public_key = new HiddenString($public_key);
        $hidden_private_key = new HiddenString($private_key);
        
        $public_key = new EncryptionPublicKey($hidden_public_key);
        $private_key = new EncryptionSecretKey($hidden_private_key);

        
        if ($request->type == 'company_users'){
            $key = Key::createNewRandomKey()->saveToAsciiSafeString();
            $encrypted = \ParagonIE\Halite\Asymmetric\Crypto::encrypt(
                new HiddenString($key),
                $private_key,
                $public_key,
            );
            
            // $encrypted = $public_key->encrypt($request->message);
            $validated = $request->validate([
                'destination_id' => 'required',
                'source_id' => 'required',
                'encrypted_message' => 'required|string',
                'type' => 'required|string',
            ]);
            
            $validated['encrypted_message'] = $encrypted;
    
            $message = RequestMessage::create($validated);
        
            $exist = DB::table('symmetric_keys')
                        ->where('company_id', '=', $request->destination_id)
                        ->where('user_id', '=', $request->source_id)
                        ->first();
            Log::debug($exist !== null);
            if ($exist){
                DB::table('symmetric_keys')
                    ->where('company_id', '=', $request->destination_id)
                    ->where('user_id', '=', $request->source_id)
                    ->update(['key' => $encrypted]);
            } else {
                SymmetricKey::create([
                    'company_id' => $request->destination_id,
                    'user_id' => $request->source_id,
                    'key' => $encrypted,
                ]);
            }

            $user = User::find($request->source_id);
            Log::debug($user->username);
        
            $metadataPath = 'idcards/' . $user->username . '_idcard_enc_metadata.json';
            if (!Storage::exists($metadataPath)) {
                session()->flash('error', 'Metadata tidak ditemukan');
                return back();
            }
            $metadata = json_decode(Storage::get($metadataPath), true);
            $pictureExtension = $metadata['fileExtension'];
            $idcard_filepath = Storage::path('idcards/' . $user->username . '_idcard_enc_' . 'aes' . $pictureExtension);
            if (!Storage::exists('idcards/' . $user->username . '_idcard_enc_' . 'aes' . $pictureExtension)) {
                session()->flash('error', 'File tidak ditemukan');
                Log::info($idcard_filepath);
                Log::info(!Storage::exists($idcard_filepath));

                return back();
            }

            Log::debug($request->user()->userKey->key);
            
            Log::debug($key);
            $this->encryptFileUsingAES(Storage::path('idcards/temp2/' . $user->username . '_idcard_enc_aes' . $pictureExtension), Storage::path('idcards/temp/' . $user->username . '_idcard_enc_aes' . $pictureExtension), $key);
            $this->encryptFileUsingRC4(Storage::path('idcards/temp2/' . $user->username . '_idcard_enc_rc4' . $pictureExtension), Storage::path('idcards/temp/' . $user->username . '_idcard_enc_rc4' . $pictureExtension), $key);
            $this->encryptFileUsingDES(Storage::path('idcards/temp2/' . $user->username . '_idcard_enc_des' . $pictureExtension), Storage::path('idcards/temp/' . $user->username . '_idcard_enc_des' . $pictureExtension), $key);

            // $this->decryptFileUsingAES(Storage::path('idcards/temp/' . $user->username . '_idcard_enc_aes' . $pictureExtension), Storage::path('idcards/temp/' . $user->username . '_idcard_enc_aes' . $pictureExtension), $key);
            // $this->decryptFileUsingRC4(Storage::path('idcards/temp/' . $user->username . '_idcard_enc_rc4' . $pictureExtension), Storage::path('idcards/temp/' . $user->username . '_idcard_enc_rc4' . $pictureExtension), $key);
            // $this->decryptFileUsingDES(Storage::path('idcards/temp/' . $user->username . '_idcard_enc_des' . $pictureExtension), Storage::path('idcards/temp/' . $user->username . '_idcard_enc_des' . $pictureExtension), $key);
        
        }
        else{
            $encrypted = \ParagonIE\Halite\Asymmetric\Crypto::encrypt(
                new HiddenString($request->encrypted_message),
                $private_key,
                $public_key,
            );
            
            // $encrypted = $public_key->encrypt($request->message);
            $validated = $request->validate([
                'destination_id' => 'required',
                'source_id' => 'required',
                'encrypted_message' => 'required|string',
                'type' => 'required|string',
            ]);
            
            $validated['encrypted_message'] = $encrypted;
    
            $message = RequestMessage::create($validated);
            $user = User::find($request->destination_id);
            Log::debug($user->username);
        
            $metadataPath = 'idcards/' . $user->username . '_idcard_enc_metadata.json';
            if (!Storage::exists($metadataPath)) {
                session()->flash('error', 'Metadata tidak ditemukan');
                return back();
            }
            $metadata = json_decode(Storage::get($metadataPath), true);
            $pictureExtension = $metadata['fileExtension'];
            $this->decryptFileUsingAES(Storage::path('idcards/' . $user->username . '_idcard_enc_aes' . $pictureExtension), Storage::path('idcards/temp2/' . $user->username . '_idcard_enc_aes' . $pictureExtension), $user->userKey->key);
            $this->decryptFileUsingRC4(Storage::path('idcards/' . $user->username . '_idcard_enc_rc4' . $pictureExtension), Storage::path('idcards/temp2/' . $user->username . '_idcard_enc_rc4' . $pictureExtension), $user->userKey->key);
            $this->decryptFileUsingDES(Storage::path('idcards/' . $user->username . '_idcard_enc_des' . $pictureExtension), Storage::path('idcards/temp2/' . $user->username . '_idcard_enc_des' . $pictureExtension), $user->userKey->key);
        }

        if ($message) {
            return response()->json(['message' => 'Request sent successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to send request'], 500);
        }
    }

    

    public function download(Request $request, $id)
    {
        $symmetric_key = DB::table('symmetric_keys')
            ->where('company_id', '=', Auth::user()->id)
            ->where('user_id', '=', $id)
            ->value('key');
        Log::debug(Auth::user()->id);
        
        $user_temp = CompanyUser::find(Auth::user()->id);
        $public_key = Storage::get('keys/' . Auth::user()->username . '.pub');
        $private_key = Storage::get('keys/' . Auth::user()->username . '.key');

        $public_key = $this->DecryptAES($public_key, $user_temp->companyKey->key);
        $private_key = $this->DecryptAES($private_key, $user_temp->companyKey->key);

        $hidden_public_key = new HiddenString($public_key);
        $hidden_private_key = new HiddenString($private_key);

        $public_key = new EncryptionPublicKey($hidden_public_key);
        $private_key = new EncryptionSecretKey($hidden_private_key);

        $decrypted = \ParagonIE\Halite\Asymmetric\Crypto::decrypt(
            $symmetric_key,
            $private_key,
            $public_key
        )->getString();

        Log::debug($decrypted);
        Log::debug($request->symmetric_key_requested);

        $user = User::find($id);
        
        $metadataPath = 'idcards/' . $user->username . '_idcard_enc_metadata.json';
        if (!Storage::exists($metadataPath)) {
            session()->flash('error', 'Metadata tidak ditemukan');
            return back();
        }
        $metadata = json_decode(Storage::get($metadataPath), true);
        $pictureExtension = $metadata['fileExtension'];
        $idcard_filepath = Storage::path('idcards/' . $user->username . '_idcard_enc_' . $request->type . $pictureExtension);
        if (!Storage::exists('idcards/' . $user->username . '_idcard_enc_' . $request->type . $pictureExtension)) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($idcard_filepath);
            Log::info(!Storage::exists($idcard_filepath));

            return back();
        }

        Log::debug(Storage::path('idcards/temp/' . $user->username . '_idcard_enc_aes' . $pictureExtension));
        Log::debug($request->symmetric_key_requested);
        Log::debug($request->type);
        $temp_id_filepath = tempnam(sys_get_temp_dir(), 'decrypted_idcard');
        switch ($request->type) {
            case "aes":
                $this->decryptFileUsingAES(Storage::path('idcards/temp/' . $user->username . '_idcard_enc_aes' . $pictureExtension), $temp_id_filepath, $request->symmetric_key_requested);
                break;
            case "rc4":
                $this->decryptFileUsingRC4(Storage::path('idcards/temp/' . $user->username . '_idcard_enc_rc4' . $pictureExtension), $temp_id_filepath, $request->symmetric_key_requested);
                break;
            case "des":
                $this->decryptFileUsingDES(Storage::path('idcards/temp/' . $user->username . '_idcard_enc_des' . $pictureExtension), $temp_id_filepath, $request->symmetric_key_requested);
                break;
            default:
                break;
        }

        $zip = new \ZipArchive();

        $zip->open(Storage::path('files/' . 'decrypted_' . $user->username . '_document.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($temp_id_filepath, 'idcard' . $pictureExtension);
        $zip->close();

        Log::debug(Storage::exists('files/' . 'decrypted_' . $user->username . '_document.zip'));

        unlink($temp_id_filepath);

        // return response()->download($temp_id_filepath, 'decrypted_' . $user->username . '_idcard' . $pictureExtension)->deleteFileAfterSend(true);

        return response()->download(Storage::path('files/' . 'decrypted_' . $user->username . '_document.zip'))->deleteFileAfterSend(true);
    }

    public function decrypt(){
        return view('request.decrypt');
    }

    public function decrypt_message(Request $request){
        $validated = $request->validate([
            'document' => 'required|mimes:jpg,jpeg,png|max:10000',
        ]);

        $document = $request->file('document');
        


    }

    public function FileUsingAES($sourcePath, $destinationPath, $decryptKey, $encryptKey){
        $cipher = 'aes-256-cbc';

        // Read the contents of the source file
        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        // Read the IV (Initialization Vector) for AES decryption
        $iv = fread($inputFile, openssl_cipher_iv_length($cipher));
        $iv2 = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

        while (!feof($inputFile)) {
            // Read chunks of the file and decrypt using the decryption key
            $ciphertext = fread($inputFile, 16 * 1024 + openssl_cipher_iv_length($cipher));
            $plaintext = openssl_decrypt($ciphertext, $cipher, $decryptKey, OPENSSL_RAW_DATA, $iv);

            // Encrypt the decrypted content using the encryption key
            // $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
            $ciphertext = openssl_encrypt($plaintext, $cipher, $encryptKey, OPENSSL_RAW_DATA, $iv2);

            // Write the encrypted content to the output file
            fwrite($outputFile, $ciphertext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }

    public function FileUsingDES($sourcePath, $destinationPath, $decryptKey, $encryptKey){
        $cipher = 'des-cbc';

        // Read the contents of the source file
        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');
        
        // Read the IV (Initialization Vector) for DES decryption
        $iv = fread($inputFile, openssl_cipher_iv_length($cipher));
        $iv2 = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        
        while (!feof($inputFile)) {
            // Read chunks of the file and decrypt using the decryption key
            $ciphertext = fread($inputFile, 8 * 1024 + openssl_cipher_iv_length($cipher));
            $plaintext = openssl_decrypt($ciphertext, $cipher, $decryptKey, OPENSSL_RAW_DATA, $iv);
            
            // Encrypt the decrypted content using the encryption key
            $ciphertext = openssl_encrypt($plaintext, $cipher, $encryptKey, OPENSSL_RAW_DATA, $iv2);
            
            // Write the encrypted content to the output file
            fwrite($outputFile, $ciphertext);
        }

        fclose($inputFile);
        fclose($outputFile);
        
    }

    public function FileUsingRC4($sourcePath, $destinationPath, $decryptKey, $encryptKey)
    {
        $cipher = 'rc4';

        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        while (!feof($inputFile)) {
            $ciphertext = fread($inputFile, 16 * 1024);
            $plaintext = openssl_decrypt($ciphertext, $cipher, $decryptKey, OPENSSL_RAW_DATA);
            $ciphertext = openssl_encrypt($plaintext, $cipher, $encryptKey, OPENSSL_RAW_DATA);
            fwrite($outputFile, $ciphertext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }
    
    public function encryptFileUsingAES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        fwrite($outputFile, $iv);

        while (!feof($inputFile)) {
            $plaintext = fread($inputFile, 16 * 1024);
            $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            fwrite($outputFile, $ciphertext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }
    
    public function decryptFileUsingAES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'aes-256-cbc';

        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        $iv = fread($inputFile, openssl_cipher_iv_length($cipher));

        while (!feof($inputFile)) {
            $ciphertext = fread($inputFile, 16 * 1024 + openssl_cipher_iv_length($cipher));
            $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            fwrite($outputFile, $plaintext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }

    public function decryptFileUsingRC4($sourcePath, $destinationPath, $key)
    {
        $cipher = 'rc4';

        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        while (!feof($inputFile)) {
            $ciphertext = fread($inputFile, 16 * 1024);
            $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA);
            fwrite($outputFile, $plaintext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }

    public function decryptFileUsingDES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'des-cbc';

        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        $iv = fread($inputFile, openssl_cipher_iv_length($cipher));

        while (!feof($inputFile)) {
            $ciphertext = fread($inputFile, 8 * 1024 + openssl_cipher_iv_length($cipher));
            $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            fwrite($outputFile, $plaintext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }


    public function encryptFileUsingRC4($sourcePath, $destinationPath, $key)
    {
        $cipher = 'rc4';

        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        while (!feof($inputFile)) {
            $plaintext = fread($inputFile, 16 * 1024);
            $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA);
            fwrite($outputFile, $ciphertext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }

    public function encryptFileUsingDES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'des-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

        $inputFile = fopen($sourcePath, 'rb');
        $outputFile = fopen($destinationPath, 'wb');

        fwrite($outputFile, $iv);

        while (!feof($inputFile)) {
            $plaintext = fread($inputFile, 8 * 1024);
            $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            fwrite($outputFile, $ciphertext);
        }

        fclose($inputFile);
        fclose($outputFile);
    }

    public function DecryptAES($ciphertext, $key){
        $cipher = 'aes-256-cbc';
        $iv = substr($key, 0, 16);
        $decrypted = openssl_decrypt(base64_decode($ciphertext), $cipher, $key, 0, $iv);
        return $decrypted;
    }

}
