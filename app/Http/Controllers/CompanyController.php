<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function dashboard()
    {
        return view('dashboard', [
            'auth' => Auth::guard('company_user')->user(),
        ]);
    }
}
