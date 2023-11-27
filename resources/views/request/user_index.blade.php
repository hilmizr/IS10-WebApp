<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Request Private Data') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($messages != null)
            @foreach ($messages as $message)
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            <header>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('Username : ') }} {{ $message->from }}
                                </h2>
                        
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $message->encrypted_message }}
                                </p>
                            </header>
                            <button class="mt-4 px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300" type="button"
                            onclick="requestIdCard('{{ $message->source_id }}', '{{ $message->from }}')">Reply</button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>

<!-- Request Message Modal -->
<div id="requestMessageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Reply for Symmetric Key </h3>
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

    {{--  function downloadIdCard(userId, username) {
        var destinationUsernameElement = document.getElementById('destinationUsername');
        destinationUsernameElement.innerText = username;
        destinationUsernameElement.setAttribute('data-user-id', userId);
        document.getElementById('downloadIDCardModal').classList.remove('hidden');
    }  --}}

    function hideModal() {
        document.getElementById('requestMessageModal').classList.add('hidden');
    }

    function sendRequestMessage(userId, message) {
        axios.post('/request-id-card', {
                destination_id: userId,
                source_id: {{ Auth::id() }},
                encrypted_message: message,
                type: 'company_users'
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
        axios.post('/request-id-card', {
                destination_id: userId,
                source_id: {{ Auth::id() }},
                encrypted_message: message,
                type: 'users'
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