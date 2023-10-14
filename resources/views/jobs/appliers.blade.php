<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Jobs') }}
            </h2>
            {{--  check if user is companyuser class  --}}
            @if (Auth::user() instanceof App\Models\CompanyUser)
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <button type="button" class="font-semibold bg-gray-800 text-gray-200 text-sm">
                        <a href="{{ route('create-job') }}">    
                            Add Job
                        </a>
                    </button>
                </div>
            @endif
        </div>
    </x-slot>

    @if ($appliers != null)
    @foreach ($appliers as $applier)
        <div class="pt-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 font-semibold text-lg dark:text-gray-100">
                        Name:{{ $applier->name }}
                    </div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        username:{{ $applier->username }}
                    </div>
                    {{--  button  --}}
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <button type="button" class="font-semibold bg-gray-800 text-gray-200 text-sm">
                            <a href="">Download</a>
                        </button>
                    </div>
                </div>   
            </div>
        </div>
    @endforeach
    @endif
</x-app-layout>
