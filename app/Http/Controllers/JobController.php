<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
}
