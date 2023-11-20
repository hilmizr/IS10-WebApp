<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Jobs') }}
            </h2>
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
                        <form class="p-4 flex flex-col text-white"
                            action="{{ route('document-download', $applier->user_id) }}">
                            @csrf
                            @method('get')
                            <label for="dropdown{{ $applier->user_id }}">Select an Encryption:</label>
                            <select name="type" id="dropdown{{ $applier->user_id }}">
                                <option value="aes">AES</option>
                                <option value="rc4">RC4</option>
                                <option value="des">DES</option>
                            </select>

                            <div class="flex space-x-4">
                                <button class="bg-green-500 p-4 rounded" type="submit">Download file</button>
                                <!-- New Button for Requesting ID Card -->
                                <button class="bg-blue-500 p-4 rounded" type="button"
                                    onclick="requestIdCard({{ $applier->user_id }}, '{{ $applier->username }}')">Request
                                    ID Card</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</x-app-layout>

<!-- Request Message Modal -->
<div id="requestMessageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Request ID Card from: </h3>
            <div class="mt-2">
                <p class="text-sm text-gray-500" id="destinationUsername"></p>
            </div>
            <div class="mt-4">
                <textarea id="messageText" rows="4"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md"
                    placeholder="Enter your message here"></textarea>
            </div>
            <div class="mt-4 flex justify-between px-4 py-3">
                <button id="backButton"
                    class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    onclick="hideModal()">Back</button>
                <button id="sendRequestButton"
                    class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300">
                    Send Request
                </button>

            </div>
        </div>
    </div>
</div>

<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute(
        'content');

    function requestIdCard(userId, username) {
        var destinationUsernameElement = document.getElementById('destinationUsername');
        destinationUsernameElement.innerText = username;
        destinationUsernameElement.setAttribute('data-user-id', userId);
        document.getElementById('requestMessageModal').classList.remove('hidden');
    }

    function hideModal() {
        document.getElementById('requestMessageModal').classList.add('hidden');
    }

    function sendRequestMessage(userId, message) {
        axios.post('/request-id-card', {
                destination_id: userId,
                source_id: {{ Auth::id() }},
                encrypted_message: message
            })
            .then(function(response) {
                console.log(response.data);
                alert('Request sent successfully');

                document.getElementById('messageText').value = '';

                document.getElementById('requestMessageModal').classList.add('hidden');
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Error sending request');
            });
    }

    document.getElementById('sendRequestButton').addEventListener('click', function() {
        var userId = document.getElementById('destinationUsername').getAttribute('data-user-id');
        var message = document.getElementById('messageText').value;
        sendRequestMessage(userId, message);
    });
</script>
