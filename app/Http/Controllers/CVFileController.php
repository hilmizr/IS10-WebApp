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

class CVFileController extends Controller
{
    public function index()
    {

        return view('upload.uploadfile', ['title' => 'Form']);
    }

    public function store(Request $request)
    {
        // Validasi
        $validated = $request->validate([
            'document' => 'required|mimes:doc,docx,pdf,xml|max:10000',
        ]);

        // Proses upload file
        $document = $request->file('document');
        $documentName = $request->user()->username . '_cv.' . $document->extension();
        $encdocumentName = $request->user()->username . '_cv_enc';
        $path = $request->file('document')->storeAs(
            'files',
            $documentName
        );
        Log::info($path);
        $key = Key::loadFromAsciiSafeString($request->user()->userKey->key);
        //File::encryptFile(Storage::path($path) ,Storage::path('files/'.$encdocumentName), $key);
        $this->encryptFileUsingAES(Storage::path($path), Storage::path('files/' . $encdocumentName . '_aes.pdf'), $request->user()->userKey->key);
        $this->encryptFileUsingRC4(Storage::path($path), Storage::path('files/' . $encdocumentName . '_rc4.pdf'), $request->user()->userKey->key);
        $this->encryptFileUsingDES(Storage::path($path), Storage::path('files/' . $encdocumentName . '_des.pdf'), $request->user()->userKey->key);

        Storage::delete($path);
        // Tampilkan flash message
        session()->flash('success', 'Data berhasil disimpan');
        return view('upload.uploadfile', ['data' => $validated, 'fileName' => $documentName, 'title' => 'Form Result']);
    }

    public function download(Request $request)
    {
        $document = $request->file('document');
        $validated = $request->validate([
            'type' => 'required|string',
        ]);
        // Specify the path to the encrypted file
        $encryptedFilePath = Storage::path('files/' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf');

        if (!Storage::exists('files/' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf')) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($encryptedFilePath);
            Log::info(!Storage::exists($encryptedFilePath));

            return redirect()->route('cv.index');
        }

        $key = Key::loadFromAsciiSafeString($request->user()->userKey->key);


        $tempFilePath = tempnam(sys_get_temp_dir(), 'decrypted_file');
        //File::decryptFile($encryptedFilePath,$tempFilePath, $key);
        switch ($request->type) {
            case "aes":
                $this->decryptFileUsingAES($encryptedFilePath, $tempFilePath,  $request->user()->userKey->key);

                break;
            case "rc4":
                $this->decryptFileUsingRC4($encryptedFilePath, $tempFilePath,  $request->user()->userKey->key);

                break;
            case "des":
                $this->decryptFileUsingDES($encryptedFilePath, $tempFilePath,  $request->user()->userKey->key);

                break;
            default:
                //TODO: do some error handling here in case there is nothing provided
                echo "aaaaaaaa";
        }

        return response()->download($tempFilePath, 'decrypted_' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf')->deleteFileAfterSend(true);
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
