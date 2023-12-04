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
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Halite\Asymmetric\EncryptionPublicKey;
use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\Halite\EncryptionKeyPair;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use ParagonIE\Halite\Asymmetric\SignaturePublicKey;
use ParagonIE\Halite\Asymmetric\SignatureSecretKey;
use phpseclib3\Crypt\RSA as CryptRSA;
use phpseclib\Crypt\RSA;

class SignPDFController extends Controller
{
    public function index()
    {

        return view('sign.uploadfile', ['title' => 'Form']);
    }

    public function signanddownload(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
        ]);
        // Specify the path to the encrypted file
        $encryptedFilePath = Storage::path('files/' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf');
        Log::debug($encryptedFilePath);

        if (!Storage::exists('files/' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf')) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($encryptedFilePath);
            Log::info(!Storage::exists($encryptedFilePath));

            return redirect()->route('cv.index3');
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

        $this->SignPdf($tempFilePath);

        return response()->download($tempFilePath, 'decrypted_' . $request->user()->username . '_cv_enc_signed_' . $request->type . '.pdf')->deleteFileAfterSend(true);
    }

    private function SignPdf($pdfPath)
    {

        // Load the private key
        $public_key = Storage::get('keys/' . Auth::user()->username . '.pub');
        $private_key = Storage::get('keys/' . Auth::user()->username . '.key');
        $private_key = $this->DecryptAES($private_key, Auth::user()->userKey->key);
        $public_key = $this->DecryptAES($public_key, Auth::user()->userKey->key);
        $private_key = $private_key . $public_key;
        $hidden_private_key = new HiddenString($private_key);
        Log::debug(strlen($hidden_private_key->getString()));
        $private_key = new SignatureSecretKey($hidden_private_key);
        // Calculate the hash of the PDF content
        $signatureContent = "Signed by " . Auth::user()->username;
        $hashedSignature = hash('sha256', $signatureContent);
        $encrypted = \ParagonIE\Halite\Asymmetric\Crypto::sign(
            $hashedSignature,
            $private_key,
        );

        // $pdfContent = file_get_contents($pdfPath);
        $signedContent = "\t\n\nSignature: " . base64_encode($encrypted);

        // append to PDF
        file_put_contents($pdfPath, $signedContent, FILE_APPEND | LOCK_EX);
    }

    public function verify(Request $request)
    {
        // Validasi
        $validated = $request->validate([
            'document' => 'required|mimes:doc,docx,pdf,xml|max:10000',
        ]);

        $public_key = Storage::get('keys/' . Auth::user()->username . '.pub');
        $private_key = Storage::get('keys/' . Auth::user()->username . '.key');
        $private_key = $this->DecryptAES($private_key, Auth::user()->userKey->key);
        $public_key = $this->DecryptAES($public_key, Auth::user()->userKey->key);
        $public_key = $private_key . $public_key;

        $hidden_public_key = new HiddenString($public_key);

        $public_key = new SignatureSecretKey($hidden_public_key);
        $signatureContent = "Signed by " . Auth::user()->username;
        $hashedSignature = hash('sha256', $signatureContent);
        $encrypted = \ParagonIE\Halite\Asymmetric\Crypto::sign(
            $hashedSignature,
            $public_key,
        );
        // Proses upload file
        $document = $request->file('document');
        $document = file_get_contents($document->path());

        $parts = explode("\t\n\nSignature: ", $document, 2);
        if (count($parts) === 2) {
            $pdfContent = $parts[0]; // Get the PDF content from the first part
            // Extract the signature from the second part
            $signaturePart = trim($parts[1]); // Remove any extra whitespace
            Log::debug($signaturePart);
            // $base64Signature = strstr($signaturePart, "\n", true); // Get the base64-encoded signature before newline
            
            // Decode the base64-encoded signature
            $signature = base64_decode($signaturePart);
        }
        Log::debug(base64_encode($encrypted));
        Log::debug(base64_encode($encrypted));
        if($signature == $encrypted){
            session()->flash('success', 'Signature Valid');
            return redirect()->route('cv.index2');
            $message = "Signature is valid!";
        }
        else{
            session()->flash('error', 'Signature Invalid');
            return redirect()->route('cv.index3');
            $message = "Signature is Invalid!";
        }
        // $signature = base64_decode($signatureBase64);
        // $pdfContent = file_get_contents($document);
        // $isSignatureValid = \ParagonIE\Halite\Asymmetric\Crypto::verify($pdfContent, $public_key, $signature);
        // Tampilkan flash message

        // return view('upload.uploadfile', ['data' => $message, '' => $documentName, 'title' => 'Form Result']);
    }

    public function DecryptAES($ciphertext, $key)
    {
        $cipher = 'aes-256-cbc';
        $iv = substr($key, 0, 16);
        $decrypted = openssl_decrypt(base64_decode($ciphertext), $cipher, $key, 0, $iv);
        return $decrypted;
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
