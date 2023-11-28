<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('ID Card Upload') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('ID Card') }}
                            </h2>
                    
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Upload Your ID Card Image') }}
                            </p>
                        </header>
                    
                        <form class="p-4 flex flex-col text-gray-900" method="post" action="{{ route('idcard.upload') }}" class="mt-6 space-y-6"
                            enctype="multipart/form-data">
                            @csrf
                            @method('post')
                            <input type="password" name="password" placeholder="password" class="border border-gray-400 p-2 rounded-lg mb-2">
                    
                            <input type="file" name="document">
                            <button class="bg-green-500 p-4 rounded text-white" type="submit">Upload ID Card</button>
                    
                        </form>
                    
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
