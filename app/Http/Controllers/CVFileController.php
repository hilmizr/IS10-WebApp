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
        $startDateTime = date('Y-m-d H:i:s.u');
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
        
        switch ($request->type) {
            case "aes":
                $this->encryptFileUsingAES(Storage::path($path), Storage::path('files/' . $encdocumentName . '_aes.pdf'), $request->user()->userKey->key);
            case "rc4":
                $this->encryptFileUsingRC4(Storage::path($path), Storage::path('files/' . $encdocumentName . '_rc4.pdf'), $request->user()->userKey->key);
            case "des":
                $this->encryptFileUsingDES(Storage::path($path), Storage::path('files/' . $encdocumentName . '_des.pdf'), $request->user()->userKey->key);
            default:
                break;
        }
        Storage::delete($path);
        // Tampilkan flash message
        session()->flash('success', 'Data berhasil disimpan');

        $endDateTime = date('Y-m-d H:i:s.u');

        return view('upload.uploadfile', ['data' => $validated, 'fileName' => $documentName, 'title' => 'Form Result']);
    }

    public function download(Request $request)
    {
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
                break;
        }

        return response()->download($tempFilePath, 'decrypted_' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf')->deleteFileAfterSend(true);
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
