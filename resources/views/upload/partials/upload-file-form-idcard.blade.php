<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('ID Card') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Upload Your ID Card Image') }}
        </p>
    </header>

    <form class="p-4 flex flex-col text-white" method="post" action="{{ route('idcard.upload') }}" class="mt-6 space-y-6"
        enctype="multipart/form-data">
        @csrf
        @method('post')

        <input type="file" name="document">
        <button class="bg-green-500 p-4 rounded" type="submit">Upload ID Card</button>

    </form>

    <form class="p-4 flex flex-col text-white" action="{{ route('idcard.download') }}">
        @csrf
        @method('get')
        <label for="dropdown">Select an Encryption:</label>
        <select name="type" id="dropdown">
            <option value="aes">AES</option>
            <option value="rc4">RC4</option>
            <option value="des">DES</option>
        </select>

        <button class="bg-green-500 p-4 rounded" type="submit">Download ID Card</button>
    </form>
</section>
