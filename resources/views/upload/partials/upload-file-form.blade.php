<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('CV') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Upload CV") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('cv.upload') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('post')

        <div>
            <input type="file" name="document">
            <button type="submit">Upload File</button>
        </div>
    </form>

    <form action="{{ route('cv.download',auth()->user()->name.'_cv_enc.pdf') }}">
        @csrf
        @method('get')
        <button type="submit">Download file</button>
    </form>

</section>
