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
        $encdocumentName = $request->user()->username . '_cv_enc.' . 'pdf';
        $path = $request->file('document')->storeAs(
            'files',  $documentName
        );
        Log::info($path);
        try {
            File::encryptFile(Storage::path($path) ,Storage::path('files/'.$encdocumentName), Key::loadFromAsciiSafeString($request->user()->userKey->key));
            Storage::delete($path);
        } catch (BadFormatException $e) {
        }
        // Tampilkan flash message
        session()->flash('success', 'Data berhasil disimpan');
        return view('upload.uploadfile', ['data' => $validated, 'fileName' => $documentName, 'title' => 'Form Result']);
    }

    public function download(Request $request, $fileName)
    {
        // Specify the path to the encrypted file
        $encryptedFilePath = Storage::path('files/'.$request->user()->username . '_cv_enc.pdf');
        if (!Storage::exists($encryptedFilePath)){
            session()->flash('error', 'File tidak ditemukan');

            return redirect()->route('cv.index');
        }

        $key = Key::loadFromAsciiSafeString($request->user()->userKey->key);


        $tempFilePath = tempnam(sys_get_temp_dir(), 'decrypted_file');
        File::decryptFile($encryptedFilePath,$tempFilePath, $key);


        return response()->download($tempFilePath, 'decrypted_' . $fileName)->deleteFileAfterSend(true);
    }
}
