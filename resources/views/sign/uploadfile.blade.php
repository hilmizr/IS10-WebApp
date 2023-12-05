<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('File Verification') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @if(Session::has('success'))
                        <div class="alert alert-success mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ Session::get('success') }}
                        </div>
                    @endif
                    @if(Session::has('error'))
                        <div class="alert alert-danger mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ Session::get('error') }}
                        </div>
                    @endif
                    @include('sign.partials.upload-file-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
