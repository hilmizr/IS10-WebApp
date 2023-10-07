<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Ohmagang</title>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Registration Successful') }}</div>
                    <!-- Registration success header -->

                    <div class="card-body">
                        <div class="alert alert-success" role="alert">
                            {{ __('Your registration was successful! You can now log in.') }}
                        </div>
                        <a href="{{ route('login') }}" class="btn btn-primary">{{ __('Log In') }}</a>
                        <!-- Log in button -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
