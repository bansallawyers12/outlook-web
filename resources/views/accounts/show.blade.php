<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Email Account Details') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('accounts.edit', $account) }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Edit Account
                </a>
                <a href="{{ route('accounts.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Accounts
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Account Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Account Information</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Provider</label>
                                <div class="mt-1 flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-semibold text-sm">
                                            {{ strtoupper(substr($account->provider, 0, 1)) }}
                                        </span>
                                    </div>
                                    <span class="text-lg font-medium">{{ ucfirst($account->provider) }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                                <p class="mt-1 text-lg">{{ $account->email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Created</label>
                                <p class="mt-1">{{ $account->created_at->format('M j, Y \a\t g:i A') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                <p class="mt-1">{{ $account->updated_at->format('M j, Y \a\t g:i A') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Authentication Method</label>
                                <p class="mt-1">
                                    @if($account->access_token)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            OAuth Token
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Password
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Connection Testing -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Connection Testing</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <button onclick="testConnection()" 
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Test Network Connection
                                </button>
                            </div>

                            <div>
                                <button onclick="testAuthentication()" 
                                        class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Test Authentication
                                </button>
                            </div>

                            <div>
                                <button onclick="syncEmails()" 
                                        class="w-full bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                    Sync Emails (Test)
                                </button>
                            </div>

                            <div id="test-results" class="mt-4 hidden">
                                <!-- Test results will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Account Statistics</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600" id="total-emails">
                                {{ \App\Models\Email::where('account_id', $account->id)->count() }}
                            </div>
                            <div class="text-sm text-blue-800">Total Emails</div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600" id="recent-emails">
                                {{ \App\Models\Email::where('account_id', $account->id)->where('created_at', '>=', now()->subDays(7))->count() }}
                            </div>
                            <div class="text-sm text-green-800">Emails (Last 7 Days)</div>
                        </div>

                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600" id="last-sync">
                                {{ \App\Models\Email::where('account_id', $account->id)->latest()->first()?->created_at?->diffForHumans() ?? 'Never' }}
                            </div>
                            <div class="text-sm text-purple-800">Last Sync</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Emails -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Recent Emails</h3>
                    
                    @php
                        $recentEmails = \App\Models\Email::where('account_id', $account->id)
                            ->orderBy('created_at', 'desc')
                            ->limit(10)
                            ->get();
                    @endphp

                    @if($recentEmails->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentEmails as $email)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">{{ $email->subject ?: 'No Subject' }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ $email->from_email }}</p>
                                            @if($email->body)
                                                <p class="text-sm text-gray-500 mt-2 line-clamp-2">
                                                    {{ Str::limit(strip_tags($email->body), 100) }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 ml-4">
                                            {{ $email->created_at->format('M j, g:i A') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No emails synced</h3>
                            <p class="mt-1 text-sm text-gray-500">Try syncing emails to see them here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function testConnection() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.classList.remove('hidden');
            resultsDiv.innerHTML = '<div class="text-yellow-600">Testing network connection...</div>';

            fetch(`/accounts/{{ $account->id }}/test-connection`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<div class="text-green-600 font-medium">Network Connection Test Results:</div>';
                    html += '<div class="mt-2 space-y-1 text-sm">';
                    html += `<div>DNS Resolution: <span class="${data.results.dns ? 'text-green-600' : 'text-red-600'}">${data.results.dns ? '✓ PASS' : '✗ FAIL'}</span></div>`;
                    html += `<div>Socket Connection: <span class="${data.results.socket ? 'text-green-600' : 'text-red-600'}">${data.results.socket ? '✓ PASS' : '✗ FAIL'}</span></div>`;
                    html += `<div>SSL Connection: <span class="${data.results.ssl ? 'text-green-600' : 'text-red-600'}">${data.results.ssl ? '✓ PASS' : '✗ FAIL'}</span></div>`;
                    html += `<div>IMAP Connection: <span class="${data.results.imap ? 'text-green-600' : 'text-red-600'}">${data.results.imap ? '✓ PASS' : '✗ FAIL'}</span></div>`;
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = `<div class="text-red-600">Connection test failed: ${data.message}</div>`;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `<div class="text-red-600">Error: ${error.message}</div>`;
            });
        }

        function testAuthentication() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.classList.remove('hidden');
            resultsDiv.innerHTML = '<div class="text-yellow-600">Testing authentication...</div>';

            fetch(`/accounts/{{ $account->id }}/test-authentication`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `<div class="text-green-600">✓ Authentication successful! ${data.message}</div>`;
                } else {
                    let html = `<div class="text-red-600">✗ Authentication failed: ${data.message}</div>`;
                    if (data.debug_info && data.debug_info.network_test) {
                        html += '<div class="mt-2 text-sm">';
                        html += '<div class="font-medium">Network Diagnostics:</div>';
                        const networkTest = data.debug_info.network_test;
                        html += `<div>DNS: ${networkTest.dns_resolution ? '✓' : '✗'}</div>`;
                        html += `<div>Socket: ${networkTest.socket_connection}</div>`;
                        html += `<div>SSL: ${networkTest.ssl_connection}</div>`;
                        html += '</div>';
                    }
                    resultsDiv.innerHTML = html;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `<div class="text-red-600">Error: ${error.message}</div>`;
            });
        }

        function syncEmails() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.classList.remove('hidden');
            resultsDiv.innerHTML = '<div class="text-yellow-600">Syncing emails (test mode)...</div>';

            fetch(`/emails/sync/{{ $account->id }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `<div class="text-green-600">✓ ${data.message}</div>`;
                    // Refresh the page to show updated statistics
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    resultsDiv.innerHTML = `<div class="text-red-600">✗ Sync failed: ${data.message}</div>`;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `<div class="text-red-600">Error: ${error.message}</div>`;
            });
        }
    </script>
</x-app-layout>
