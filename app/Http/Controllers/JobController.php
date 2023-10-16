<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    public function index()
    {
        return view('jobs.index',[
            'jobs' => Job::all()
        ]);
    }

    public function company_index()
    {
        return view('jobs.index', [
            'jobs' => Job::where('company_user_id', auth()->user()->id)->get()
        ]);
    }

    public function create()
    {
        return view('jobs.create-form');
    }

    public function store(Request $request){
        // dd(Auth::user()->id);
        $id = Auth::user()->id;
        $job = Job::create([
            'company_user_id' => $id,
            'title' => $request->title,
            'description' => $request->description,
            'salary' => $request->salary,
            'location' => $request->location,
            'requirements' => $request->requirements,
        ]);
        return redirect()->route('company-job.index');

    }

    public function edit($id){
        $job = Job::find($id);
        return view('jobs.edit-form', [
            'job' => $job
        ]);
    }

    public function update(Request $request, $id){
        $job = Job::find($id);
        $job->update([
            'title' => $request->title,
            'description' => $request->description,
            'salary' => $request->salary,
            'location' => $request->location,
            'requirements' => $request->requirements,
        ]);
        return redirect()->route('company-job.index');
    }

    public function destroy($id){
        $job = Job::find($id);
        $job->delete();
        return redirect()->route('company-job.index');
    }

    public function apply($id){
        $exist = DB::table('job_user')
            ->where('user_id', Auth::user()->id)
            ->where('job_id', $id)
            ->first();
        
        
        if($exist  === null){
            $job = Job::find($id);
            
            DB::table('job_user')->insert([
                'job_id' => $job->id,
                'user_id' => Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return redirect()->route('job.index');

    }

    public function appliers($id){

        $appliers = DB::table('users')
            ->join('job_user', 'users.id', '=', 'job_user.user_id')
            ->where('job_user.job_id', $id)
            ->get();
        
        return view('jobs.appliers', [
            'appliers' => $appliers
        ]);
        
    }

    public function document_download(Request $request, $id){
        $document = $request->file('document');
        $validated = $request->validate([
            'type' => 'required|string',
        ]);
        // Specify the path to the encrypted file
        
        $metadataPath = 'files/' . $request->user()->username . '_cv_enc_metadata.json';
        if (!Storage::exists($metadataPath)) {
            session()->flash('error', 'Metadata tidak ditemukan');
            return back();
        }
        $metadata = json_decode(Storage::get($metadataPath), true);
        $fileExtension = $metadata['fileExtension'];
        $idcard_filepath = Storage::path('idcards/' . $request->user()->username . '_idcard_enc_' . $request->type . $fileExtension);
        if (!Storage::exists($idcard_filepath)) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($idcard_filepath);
            Log::info(!Storage::exists($idcard_filepath));
            
            return back();
        }
        
        $cv_filepath = Storage::path('files/' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf');
        if (!Storage::exists('files/' . $request->user()->username . '_cv_enc_' . $request->type . '.pdf')) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($cv_filepath);
            Log::info(!Storage::exists($cv_filepath));

            return back();
        }

        $video_filepath = Storage::path('files/' . $request->user()->username . '_video_' . $request->type . '.mp4');
        if (!Storage::exists('files/' . $request->user()->username . '_video_' . $request->type . '.mp4')) {
            session()->flash('error', 'File tidak ditemukan');
            Log::info($video_filepath);
            Log::info(!Storage::exists($video_filepath));

            return back();
        }

        $temp_id_filepath = tempnam(sys_get_temp_dir(), 'decrypted_idcard');
        $temp_cv_filepath = tempnam(sys_get_temp_dir(), 'decrypted_cv');
        $temp_video_filepath = tempnam(sys_get_temp_dir(), 'decrypted_video');

        switch ($request->type){
            case "aes":
                $this->decryptFileUsingAES($idcard_filepath, $temp_id_filepath, $request->user()->userKey->key);
                $this->decryptFileUsingAES($cv_filepath, $temp_cv_filepath, $request->user()->userKey->key);
                $this->decryptFileUsingAES($video_filepath, $temp_video_filepath, $request->user()->userKey->key);
                break;
            case "rc4":
                $this->decryptFileUsingRC4($idcard_filepath, $temp_id_filepath, $request->user()->userKey->key);
                $this->decryptFileUsingRC4($cv_filepath, $temp_cv_filepath, $request->user()->userKey->key);
                $this->decryptFileUsingRC4($video_filepath, $temp_video_filepath, $request->user()->userKey->key);
                break;
            case "des":
                $this->decryptFileUsingDES($idcard_filepath, $temp_id_filepath, $request->user()->userKey->key);
                $this->decryptFileUsingDES($cv_filepath, $temp_cv_filepath, $request->user()->userKey->key);
                $this->decryptFileUsingDES($video_filepath, $temp_video_filepath, $request->user()->userKey->key);
                break;
            default:
                echo "aaaaaaaa";

        }
        // zip 3 files
        $zip = new \ZipArchive();
        $zip->open(Storage::path('files/' . $request->user()->username . '_document.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($temp_id_filepath, 'idcard' . $fileExtension);
        $zip->addFile($temp_cv_filepath, 'cv.pdf');
        $zip->addFile($temp_video_filepath, 'video.mp4');
        $zip->close();

        // delete temp files
        unlink($temp_id_filepath);
        unlink($temp_cv_filepath);
        unlink($temp_video_filepath);

        // download zip
        return response()->download(Storage::path('files/' . 'decrypted_' . $request->user()->username . '_document.zip'))->deleteFileAfterSend(true);


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

    public function decryptFileUsingRC4($sourcePath, $destinationPath, $key)
    {
        $cipher = 'rc4';

        $data = file_get_contents($sourcePath);
        $decryptedData = openssl_decrypt($data, $cipher, $key);

        file_put_contents($destinationPath, $decryptedData);
    }

    public function decryptFileUsingDES($sourcePath, $destinationPath, $key)
    {
        $cipher = 'des-ecb';

        $data = file_get_contents($sourcePath);

        $decryptedData = openssl_decrypt($data, $cipher, $key, $options = 0);

        file_put_contents($destinationPath, $decryptedData);
    }
}
