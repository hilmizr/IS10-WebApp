<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormController extends Controller
{
    public function index()
    {
        return view('form', ['title' => 'Form']);
    }

    public function store(Request $request)
    {
        // Validasi
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'required|integer',
            'doubleField' => 'required|between:2.50,99.99',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Proses upload gambar
        $image = $request->file('image');
        $imageName = time() . '.' . $image->extension();
        $image->move(public_path('images'), $imageName);

        // Tampilkan flash message
        session()->flash('success', 'Form berhasil disimpan');

        return view('result', ['data' => $validated, 'imageName' => $imageName, 'title' => 'Form Result']);
    }
}
