<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Video') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Upload Your Video') }}
        </p>
    </header>

    <form class="p-4 flex flex-col text-gray-900" method="post" action="{{ route('video.upload') }}" class="mt-6 space-y-6"
        enctype="multipart/form-data">
        @csrf
        @method('post')
        <label for="dropdown">Select an Encryption:</label>
        <select name="type" id="dropdown">
            <option value="aes">AES</option>
            <option value="rc4">RC4</option>
            <option value="des">DES</option>
        </select>

        <input type="file" name="document">
        <button class="bg-green-500 p-4 rounded text-white" type="submit">Upload Video</button>

    </form>

    <form class="p-4 flex flex-col text-gray-900" action="{{ route('video.download') }}">
        @csrf
        @method('get')
        <label for="dropdown">Select an Encryption:</label>
        <select name="type" id="dropdown">
            <option value="aes">AES</option>
            <option value="rc4">RC4</option>
            <option value="des">DES</option>
        </select>

        <button class="bg-green-500 p-4 rounded text-white" type="submit">Download Video</button>
    </form>
</section>
