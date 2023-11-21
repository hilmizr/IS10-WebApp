<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RequestMessage;
use App\Models\SymmetricKey;
use App\Models\User;
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

        $hidden_public_key = new HiddenString($public_key);
        $hidden_private_key = new HiddenString($private_key);

        $public_key = new EncryptionPublicKey($hidden_public_key);
        $private_key = new EncryptionSecretKey($hidden_private_key);
        
        $encrypted = \ParagonIE\Halite\Asymmetric\Crypto::encrypt(
            new HiddenString(
                $request->encrypted_message
            ),
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

        if ($request->type == 'company_users'){
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
        $public_key = Storage::get('keys/' . Auth::user()->username . '.pub');
        $private_key = Storage::get('keys/' . Auth::user()->username . '.key');

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
        if($decrypted === $request->symmetric_key_requested){
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

            Log::debug($idcard_filepath);
            $temp_id_filepath = tempnam(sys_get_temp_dir(), 'decrypted_idcard');
            switch ($request->type) {
                case "aes":
                    $this->decryptFileUsingAES($idcard_filepath, $temp_id_filepath, $user->userKey->key);
                    break;
                case "rc4":
                    $this->decryptFileUsingRC4($idcard_filepath, $temp_id_filepath, $user->userKey->key);
                    break;
                case "des":
                    $this->decryptFileUsingDES($idcard_filepath, $temp_id_filepath, $user->userKey->key);
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

            return response()->download(Storage::path('files/' . 'decrypted_' . $user->username . '_document.zip'))->deleteFileAfterSend(true);
        }
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
}
