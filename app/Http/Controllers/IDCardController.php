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

class IDCardController extends Controller
{
    public function index()
    {
        return view('upload.uploadIDCard', ['title' => 'Form']);
    }

    public function store(Request $request)
    {
        $startDateTime = date('Y-m-d H:i:s.u');
        // Validation
        $validated = $request->validate([
            'document' => 'required|mimes:jpg,jpeg,png|max:10000',
        ]);

        // Process file upload
        $document = $request->file('document');
        $documentName = $request->user()->username . '_idcard.' . $document->extension();
        $encdocumentName = $request->user()->username . '_idcard_enc';
        $path = $request->file('document')->storeAs(
            'idcards',
            $documentName
        );

        $fileExtension = '.' . $document->extension();

        // Save metadata
        $metadata = [
            "fileExtension" => $fileExtension
        ];
        $metadataPath = 'idcards/' . $request->user()->username . '_idcard_enc_metadata.json';
        Storage::put($metadataPath, json_encode($metadata));

        Log::info($path);
        $key = Key::loadFromAsciiSafeString($request->user()->userKey->key);

        switch ($request->type) {
            case "aes":
                $this->encryptFileUsingAES(Storage::path($path), Storage::path('idcards/' . $encdocumentName . '_aes' . $fileExtension), $request->user()->userKey->key);
            case "rc4":
                $this->encryptFileUsingRC4(Storage::path($path), Storage::path('idcards/' . $encdocumentName . '_rc4' . $fileExtension), $request->user()->userKey->key);
            case "des":
                $this->encryptFileUsingDES(Storage::path($path), Storage::path('idcards/' . $encdocumentName . '_des' . $fileExtension), $request->user()->userKey->key);
                // $this->decryptFileUsingDES(Storage::path($path), Storage::path('idcards/' . $encdocumentName . '_des' . $fileExtension), $request->user()->userKey->key);
            default:
                break;
        }

        Storage::delete($path);

        // Display flash message
        session()->flash('success', 'Data berhasil disimpan');

        $endDateTime = date('Y-m-d H:i:s.u');

        return view('upload.uploadIDCard', ['data' => $validated, 'fileName' => $documentName, 'title' => 'Form Result']);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
        ]);

        // Fetch metadata
        $metadataPath = 'idcards/' . $request->user()->username . '_idcard_enc_metadata.json';
        if (!Storage::exists($metadataPath)) {
            session()->flash('error', 'Metadata tidak ditemukan');
            return redirect()->route('idcard.index');
        }
        $metadata = json_decode(Storage::get($metadataPath), true);
        $fileExtension = $metadata['fileExtension'];

        // Specify the path to the encrypted file
        $encryptedFilePath = Storage::path('idcards/' . $request->user()->username . '_idcard_enc_' . $request->type . $fileExtension);

        if (!Storage::exists('idcards/' . $request->user()->username . '_idcard_enc_' . $request->type . $fileExtension)) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($encryptedFilePath);
            Log::info(!Storage::exists($encryptedFilePath));

            return redirect()->route('idcard.index');
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

        return response()->download($tempFilePath, 'decrypted_' . $request->user()->username . '_idcard_enc_' . $request->type . $fileExtension)->deleteFileAfterSend(true);
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
