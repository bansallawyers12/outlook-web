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
                                 {{ $account->email_count }}
                             </div>
                             <div class="text-sm text-blue-800">Total Emails</div>
                         </div>

                         <div class="bg-green-50 p-4 rounded-lg">
                             <div class="text-2xl font-bold text-green-600" id="recent-emails">
                                 {{ $account->recent_email_count }}
                             </div>
                             <div class="text-sm text-green-800">Emails (Last 7 Days)</div>
                         </div>

                         <div class="bg-purple-50 p-4 rounded-lg">
                             <div class="text-2xl font-bold text-purple-600" id="last-sync">
                                 {{ $account->last_sync ?? 'Never' }}
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
                         $recentEmails = $account->emails()
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
                                            <p class="text-sm text-gray-600 mt-1">From: {{ $email->sender_name ? $email->sender_name . ' <' . ($email->sender_email ?: $email->from_email) . '>' : ($email->sender_email ?: $email->from_email) }}</p>
                                            <p class="text-sm text-gray-600">To: {{ is_array($email->recipients) ? implode(', ', $email->recipients) : ($email->to_email ?? '') }}</p>
                                            @if($email->body)
                                                <p class="text-sm text-gray-500 mt-2 line-clamp-2">
                                                    {{ Str::limit(strip_tags($email->body), 100) }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 ml-4">
                                            {{ optional($email->sent_date)->format('F j, Y \a\t g:i A') ?? optional($email->received_at)->format('F j, Y \a\t g:i A') ?? $email->created_at->format('F j, Y \a\t g:i A') }}
                                        </div>
                                    </div>

                                    @php $attachments = $email->attachments; @endphp
                                    @if($attachments && $attachments->count())
                                        <div class="mt-3 border-t pt-3">
                                            <div class="text-sm font-medium text-gray-700 mb-2">Attachments ({{ $attachments->count() }})</div>
                                            <div class="space-y-2">
                                                @foreach($attachments as $att)
                                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded border">
                                                        <div class="flex items-center space-x-2">
                                                            <!-- File type icon -->
                                                            <div class="flex-shrink-0">
                                                                @if($att->isImage())
                                                                    <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
                                                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                @elseif($att->isPdf())
                                                                    <div class="w-6 h-6 bg-red-100 rounded flex items-center justify-center">
                                                                        <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                @else
                                                                    <div class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center">
                                                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900">{{ $att->display_name ?: $att->filename }}</div>
                                                                <div class="text-xs text-gray-500">{{ $att->formatted_file_size }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center space-x-1">
                                                            @if($att->canPreview())
                                                                <a href="{{ route('attachments.view', $att->id) }}" target="_blank" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200" title="View">
                                                                    View
                                                                </a>
                                                            @endif
                                                            <a href="{{ route('attachments.download', $att->id) }}" class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200" title="Download">
                                                                Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mt-3 flex items-center gap-2">
                                        <select id="label-select-{{ $email->id }}" class="border rounded px-2 py-1 text-sm">
                                            <option value="">Add label...</option>
                                        </select>
                                        <button onclick="applyLabel({{ $email->id }})" class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm px-3 py-1 rounded">Apply</button>
                                        <div id="labels-list-{{ $email->id }}" class="flex flex-wrap gap-1 ml-2"></div>
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

        // Labels UI helpers
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const res = await fetch('{{ route('labels.index') }}');
                const labels = await res.json();
                document.querySelectorAll('[id^="label-select-"]').forEach(sel => {
                    labels.forEach(l => {
                        const opt = document.createElement('option');
                        opt.value = l.id;
                        opt.textContent = l.name;
                        sel.appendChild(opt);
                    });
                });
            } catch (e) {}
        });

        async function applyLabel(emailId) {
            const select = document.getElementById(`label-select-${emailId}`);
            const labelId = select.value;
            if (!labelId) return;
            const res = await fetch('{{ route('labels.apply') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email_id: emailId, label_id: labelId })
            });
            if (res.ok) {
                location.reload();
            }
        }
    </script>
</x-app-layout>
