@extends('layouts.main')

@section('container')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Result</div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="text-center">
                        <img src="{{ asset('images/' . $imageName) }}" alt="Uploaded Image" class="img-fluid"
                            style="max-width: 150px; margin-bottom: 20px;">
                        <h3>{{ $data['name'] }}</h3>
                    </div>

                    <div class="mb-3">
                        <strong>Email:</strong> {{ $data['email'] }}
                    </div>
                    <div class="mb-3">
                        <strong>Age:</strong> {{ $data['age'] }}
                    </div>
                    <div class="mb-3">
                        <strong>Double Field:</strong> {{ $data['doubleField'] }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
