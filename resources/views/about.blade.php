@extends('layouts.main')

@section('container')
    <h1>About Page</h1>
    <h3>{{ $name }}</h3>
    <p>{{ $email }}</p>
    <img src="images/{{ $image }}" alt={{ $name }} width="200">
@endsection
