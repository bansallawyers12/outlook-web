<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Email Accounts') }}
            </h2>
            <a href="{{ route('accounts.create') }}" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add New Account
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if($accounts->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($accounts as $account)
                                <div class="bg-white border border-gray-200 rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-600 font-semibold text-sm">
                                                    {{ strtoupper(substr($account->provider, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    {{ ucfirst($account->provider) }}
                                                </h3>
                                                <p class="text-sm text-gray-600">{{ $account->email }}</p>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button onclick="testConnection({{ $account->id }})" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Test
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div class="text-sm text-gray-500">
                                            Added {{ $account->created_at->diffForHumans() }}
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('accounts.edit', $account) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Edit
                                            </a>
                                            <form action="{{ route('accounts.destroy', $account) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this account?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Connection Status -->
                                    <div id="connection-status-{{ $account->id }}" class="mt-3 hidden">
                                        <div class="text-sm">
                                            <span class="font-medium">Connection Status:</span>
                                            <span id="status-text-{{ $account->id }}"></span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No email accounts</h3>
                        <p class="text-gray-500 mb-4">Get started by adding your first email account.</p>
                        <a href="{{ route('accounts.create') }}" 
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Add Email Account
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Test Results Modal -->
    <div id="testModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Connection Test Results</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="testResults" class="text-sm">
                    <!-- Test results will be displayed here -->
                </div>
                <div class="mt-4">
                    <button onclick="closeModal()" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testConnection(accountId) {
            const statusDiv = document.getElementById(`connection-status-${accountId}`);
            const statusText = document.getElementById(`status-text-${accountId}`);
            
            statusDiv.classList.remove('hidden');
            statusText.textContent = 'Testing...';
            statusText.className = 'text-yellow-600';

            fetch(`/accounts/${accountId}/test-connection`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusText.textContent = 'Connected ✓';
                    statusText.className = 'text-green-600';
                    showTestResults(data.results);
                } else {
                    statusText.textContent = 'Failed ✗';
                    statusText.className = 'text-red-600';
                }
            })
            .catch(error => {
                statusText.textContent = 'Error ✗';
                statusText.className = 'text-red-600';
                console.error('Error:', error);
            });
        }

        function showTestResults(results) {
            const modal = document.getElementById('testModal');
            const resultsDiv = document.getElementById('testResults');
            
            let html = '<div class="space-y-2">';
            html += `<div class="flex justify-between"><span>DNS Resolution:</span> <span class="${results.dns ? 'text-green-600' : 'text-red-600'}">${results.dns ? '✓ PASS' : '✗ FAIL'}</span></div>`;
            html += `<div class="flex justify-between"><span>Socket Connection:</span> <span class="${results.socket ? 'text-green-600' : 'text-red-600'}">${results.socket ? '✓ PASS' : '✗ FAIL'}</span></div>`;
            html += `<div class="flex justify-between"><span>SSL Connection:</span> <span class="${results.ssl ? 'text-green-600' : 'text-red-600'}">${results.ssl ? '✓ PASS' : '✗ FAIL'}</span></div>`;
            html += `<div class="flex justify-between"><span>IMAP Connection:</span> <span class="${results.imap ? 'text-green-600' : 'text-red-600'}">${results.imap ? '✓ PASS' : '✗ FAIL'}</span></div>`;
            html += '</div>';
            
            resultsDiv.innerHTML = html;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('testModal').classList.add('hidden');
        }
    </script>
</x-app-layout>
