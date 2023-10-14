<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Job Information') }}
                            </h2>
                    
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __("Create Job Information for people to apply.") }}
                            </p>
                        </header>

                    
                        <form method="post" action="/edit-job/{{ $job->id }}" class="mt-6 space-y-6">
                            @csrf
                            @method('post')
                    
                            <div>
                                <x-input-label for="Title" :value="__('Title')" />
                                <x-text-input id="Title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $job->title)" required autofocus autocomplete="name" />
                                {{--  <x-input-error class="mt-2" :messages="$errors->get('name')" />  --}}
                            </div>
                    
                    
                            <div>
                                <x-input-label for="description" :value="__('Description')" />
                                <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $job->description)"  autofocus autocomplete="address" />
                                {{--  <x-input-error class="mt-2" :messages="$errors->get('address')" />  --}}
                            </div>

                            <div>
                                <x-input-label for="requirements" :value="__('Requirements')" />
                                <x-text-input id="requirements" name="requirements" type="Text" class="mt-1 block w-full" :value="old('requirements', $job->requirements)"  autofocus autocomplete="phone" />
                                {{--  <x-input-error class="mt-2" :messages="$errors->get('phone')" />  --}}
                            </div>
                    
                            <div>
                                <x-input-label for="location" :value="__('Location')" />
                                <x-text-input id="location" name="location" type="Text" class="mt-1 block w-full" :value="old('location', $job->location)"  autofocus autocomplete="phone" />
                                {{--  <x-input-error class="mt-2" :messages="$errors->get('phone')" />  --}}
                            </div>

                            <div>
                                <x-input-label for="salary" :value="__('Salary')" />
                                <x-text-input id="salary" name="salary" type="number" class="mt-1 block w-full" :value="old('salary', $job->salary)"  autofocus autocomplete="phone" />
                                {{--  <x-input-error class="mt-2" :messages="$errors->get('phone')" />  --}}
                            </div>
                    
                    
                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                    
                                @if (session('status') === 'profile-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >{{ __('Saved.') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
