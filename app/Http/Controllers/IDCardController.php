<?php

namespace App\Http\Controllers;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Defuse\Crypto\Key;
use Defuse\Crypto\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class IDCardController extends Controller
{
    public function index()
    {
        return view('upload.uploadIDCard', ['title' => 'ID Card Form']);
    }

    public function store(Request $request)
    {
        // dd("upload hit");
        // Validation
        $validated = $request->validate([
            'document' => 'required|mimes:jpg,jpeg,png|max:10000',
        ]);

        // Process upload file
        $document = $request->file('document');
        $documentName = $request->user()->username . '_idcard.' . $document->extension();
        $encdocumentName = $request->user()->username . '_idcard_enc';
        $path = $request->file('document')->storeAs(
            'idcards',
            $documentName
        );

        // Store the file extension in the session
        session(['idcard_file_extension' => $document->extension()]);

        Log::info($path);
        $key = Key::loadFromAsciiSafeString($request->user()->userKey->key);
        $this->encryptFileUsingAES(Storage::path($path), Storage::path('idcards/' . $encdocumentName . '_aes.' . $document->extension()), $request->user()->userKey->key);
        $this->encryptFileUsingRC4(Storage::path($path), Storage::path('idcards/' . $encdocumentName . '_rc4.' . $document->extension()), $request->user()->userKey->key);
        $this->encryptFileUsingDES(Storage::path($path), Storage::path('idcards/' . $encdocumentName . '_des.' . $document->extension()), $request->user()->userKey->key);

        Storage::delete($path);

        // Display flash message
        session()->flash('success', 'Data berhasil disimpan');
        return view('upload.uploadIDCard', ['data' => $validated, 'fileName' => $documentName, 'title' => 'ID Card Form Result']);
    }

    public function download(Request $request)
    {
        // dd("download hit");
        $validated = $request->validate([
            'type' => 'required|string',
        ]);

        // Retrieve the file extension from the session
        $fileExtension = session('idcard_file_extension', 'jpg');

        // Specify the path to the encrypted file using the retrieved file extension
        $encryptedFilePath = Storage::path('idcards/' . $request->user()->username . '_idcard_enc_' . $request->type . '.' . $fileExtension);
        
        if (!Storage::exists($encryptedFilePath)) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($encryptedFilePath);

            return redirect()->route('idcard.index');
        }

        $key = Key::loadFromAsciiSafeString($request->user()->userKey->key);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'decrypted_idcard');
        switch ($request->type) {
            case "aes":
                $this->decryptFileUsingAES($encryptedFilePath, $tempFilePath, $request->user()->userKey->key);
                break;
            case "rc4":
                $this->decryptFileUsingRC4($encryptedFilePath, $tempFilePath, $request->user()->userKey->key);
                break;
            case "des":
                $this->decryptFileUsingDES($encryptedFilePath, $tempFilePath, $request->user()->userKey->key);
                break;
            default:
                //TODO: do some error handling here in case there is nothing provided
                echo "Error!";
        }

        return response()->download($tempFilePath, 'decrypted_' . $request->user()->username . '_idcard_enc_' . $request->extension . '.' . $request->type)->deleteFileAfterSend(true);
    }

    public function encryptFileUsingAES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        $data = file_get_contents($sourcePath);
        $encryptedData = openssl_encrypt($data, $cipher, $key, 0, $iv);

        // Write the encrypted data to the destination file
        file_put_contents($destinationPath, $iv . $encryptedData);
    }

    public function decryptFileUsingAES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'aes-256-cbc';
        $data = file_get_contents($sourcePath);
        $iv = substr($data, 0, openssl_cipher_iv_length($cipher));

        $data = substr($data, openssl_cipher_iv_length($cipher));
        $decryptedData = openssl_decrypt($data, $cipher, $key, 0, $iv);

        // Write the decrypted data to the destination file
        file_put_contents($destinationPath, $decryptedData);
    }
    public function encryptFileUsingRC4($sourcePath, $destinationPath, $key)
    {
        $cipher = 'rc4';

        $data = file_get_contents($sourcePath);
        $encryptedData = openssl_encrypt($data, $cipher, $key);

        // Write the encrypted data to the destination file
        file_put_contents($destinationPath, $encryptedData);
    }

    public function decryptFileUsingRC4($sourcePath, $destinationPath, $key)
    {
        $cipher = 'rc4';

        $data = file_get_contents($sourcePath);
        $decryptedData = openssl_decrypt($data, $cipher, $key);

        file_put_contents($destinationPath, $decryptedData);
    }

    public function encryptFileUsingDES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'des-ecb';

        $data = file_get_contents($sourcePath);

        $encryptedData = openssl_encrypt($data, $cipher, $key, $options = 0);

        file_put_contents($destinationPath, $encryptedData);
    }

    public function decryptFileUsingDES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'des-ecb';

        $data = file_get_contents($sourcePath);

        $decryptedData = openssl_decrypt($data, $cipher, $key, $options = 0);

        file_put_contents($destinationPath, $decryptedData);
    }
}
