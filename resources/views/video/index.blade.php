<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Video Upload') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                <section>
                    <header>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            {{ __('Video') }}
                        </h1>

                        <h2 class="mt-1 text-lg font-semibold text-black dark:text-gray-400 mb-2">
                            {{ __("Upload Resume Video") }}
                        </h2>
                    </header>

                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                    </form>

                    <form class="flex flex-col" method="post" action="{{ route('video.store') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
                        @csrf
                        @method('post')

                        <input type="file" name="video" class="mb-2">

                        <label for="dropdown" class="mb-2">Select an Encryption:</label>
                        <select name="type" id="dropdown" class="mb-2">
                            <option value="aes">AES</option>
                            <option value="rc4">RC4</option>
                            <option value="des">DES</option>
                        </select>

                        
                        <button class="bg-green-500 p-4 rounded text-white mb-6" type="submit">Upload File</button>

                    </form>

                    <h2 class="mt-1 text-lg font-semibold text-black dark:text-gray-400 mb-2">
                        {{ __("Download Resume Video") }}
                    </h2>

                    <form class="flex flex-col text-white" action="{{ route('video.download') }}">
                        @csrf
                        @method('get')
                        <button class="bg-green-500 p-4 rounded" type="submit">Download File</button>
                    </form>

                </section>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
