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

    @foreach ($jobs as $job)
        <div class="pt-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 font-semibold text-lg dark:text-gray-100">
                        {{ $job->title }}
                    </div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        Description:{{ $job->description }}
                    </div>
                    {{--  button  --}}
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        @if (Auth::user() instanceof App\Models\CompanyUser)
                        <form action="{{ route('delete-job', ['id' => $job->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this job?');">
                            <button type="button" class="font-semibold bg-gray-800 text-gray-200 text-sm">
                                <a href="{{ route('appliers', ['id' => $job->id]) }}">    
                                    Appliers
                                </a>
                            </button>
                            <button type="button" class="font-semibold bg-gray-800 text-gray-200 text-sm">
                                <a href="{{ route('edit-job', ['id' => $job->id]) }}">    
                                    Edit
                                </a>
                            </button>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold bg-gray-800 text-gray-200 text-sm">Delete</button>
                        </form>
                        @else
                            <button type="button" class="font-semibold bg-gray-800 text-gray-200 text-sm">
                                <a href="{{ route('apply-job', ['id' => $job->id]) }}">    
                                    Apply
                                </a>
                            </button>
                        @endif
                </div>   
            </div>
        </div>
    @endforeach
</x-app-layout>
