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

class VideoController extends Controller
{
    public function index()
    {
        return view('video.index', ['title' => 'Form']);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
           'video' => 'required|file|mimetypes:video/mp4',
           'type' => 'required'
        ]);   

        $video = $request->file('video');
        $videoName = $request->user()->username . '_video.' . $video->extension();
        $encvideoName = $request->user()->username . '_video_enc';
        $path = $request->file('video')->storeAs(
            'videos',
            $videoName
        );

        Log::info($path);
        $key=Key::loadFromAsciiSafeString($request->user()->userKey->key);

        switch ($request->type) {
            case 'aes':
                $this->encryptFileUsingAES(Storage::path($path) ,Storage::path('files/' . $encvideoName . '_aes.mp4'), $request->user()->userKey->key );

            case 'rc4':
                $this->encryptFileUsingRC4(Storage::path($path) ,Storage::path('files/' . $encvideoName . '_rc4.mp4'), $request->user()->userKey->key );
            
            case 'des':
                $this->encryptFileUsingDES(Storage::path($path) ,Storage::path('files/' . $encvideoName . '_des.mp4'), $request->user()->userKey->key );    
        }

        Storage::delete($path);

        return redirect()->back()->with('success', 'Video uploaded successfully.');
    }

    public function download(Request $request)
    {
        $type = $request->user()->resume_video;
        
        $encryptedFilePath = Storage::path('files/'.$request->user()->username . '_video_'.$type.'.mp4');

        if (!Storage::exists($encryptedFilePath)){
            session()->flash('error', 'File tidak ditemukan');
            Log::info($encryptedFilePath);
            Log::info(!Storage::exists($encryptedFilePath));

            return redirect()->route('video.index');
        }

        $key = Key::loadFromAsciiSafeString($request->user()->userKey->key);

        $tempFilePath = Storage::path('public/videos/temp.mp4');

        

        switch ($type) {
            case "aes":
                $this->decryptFileUsingAES($encryptedFilePath,$tempFilePath,  $request->user()->userKey->key);
                break;
            case "rc4":
                $this->decryptFileUsingRC4($encryptedFilePath,$tempFilePath,  $request->user()->userKey->key);

                break;
            case "des":
                $this->decryptFileUsingDES($encryptedFilePath,$tempFilePath,  $request->user()->userKey->key);

                break;
            default:
                //TODO: do some error handling here in case there is nothing provided
                break;
        }

        return response()->download($tempFilePath, 'decrypted_'.$request->user()->username . '_video_'.$type.'.mp4')->deleteFileAfterSend(true);
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
