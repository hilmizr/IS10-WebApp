<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    }
}
