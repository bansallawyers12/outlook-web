<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Email Account') }}
            </h2>
            <a href="{{ route('accounts.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Accounts
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('accounts.update', $account) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Provider Selection -->
                        <div>
                            <label for="provider" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Provider
                            </label>
                            <select name="provider" id="provider" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('provider') border-red-500 @enderror"
                                    required>
                                <option value="">Select a provider</option>
                                <option value="zoho" {{ old('provider', $account->provider) == 'zoho' ? 'selected' : '' }}>Zoho Mail</option>
                                <option value="gmail" {{ old('provider', $account->provider) == 'gmail' ? 'selected' : '' }}>Gmail</option>
                                <option value="outlook" {{ old('provider', $account->provider) == 'outlook' ? 'selected' : '' }}>Outlook/Hotmail</option>
                            </select>
                            @error('provider')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email" name="email" id="email" 
                                   value="{{ old('email', $account->email) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                                   placeholder="your.email@example.com"
                                   required>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Password / App Password
                            </label>
                            <input type="password" name="password" id="password" 
                                   value="{{ old('password', $account->password) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                                   placeholder="Enter your password or app-specific password"
                                   required>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                For Gmail and Zoho, you may need to use an app-specific password instead of your regular password.
                            </p>
                        </div>

                        <!-- Access Token (Optional) -->
                        <div>
                            <label for="access_token" class="block text-sm font-medium text-gray-700 mb-2">
                                Access Token (Optional)
                            </label>
                            <input type="text" name="access_token" id="access_token" 
                                   value="{{ old('access_token', $account->access_token) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('access_token') border-red-500 @enderror"
                                   placeholder="OAuth access token (if using OAuth)">
                            @error('access_token')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Leave empty if using password authentication.
                            </p>
                        </div>

                        <!-- Refresh Token (Optional) -->
                        <div>
                            <label for="refresh_token" class="block text-sm font-medium text-gray-700 mb-2">
                                Refresh Token (Optional)
                            </label>
                            <input type="text" name="refresh_token" id="refresh_token" 
                                   value="{{ old('refresh_token', $account->refresh_token) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('refresh_token') border-red-500 @enderror"
                                   placeholder="OAuth refresh token (if using OAuth)">
                            @error('refresh_token')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Test Connection Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                            <h4 class="text-sm font-medium text-gray-800 mb-2">Test Connection</h4>
                            <p class="text-sm text-gray-600 mb-3">
                                Test your account settings before saving to ensure they work correctly.
                            </p>
                            <div class="flex space-x-4">
                                <button type="button" onclick="testConnection()" 
                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Test Network Connection
                                </button>
                                <button type="button" onclick="testAuthentication()" 
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Test Authentication
                                </button>
                            </div>
                            <div id="test-results" class="mt-3 hidden">
                                <!-- Test results will be displayed here -->
                            </div>
                        </div>

                        <!-- Help Information -->
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Setup Instructions:</h4>
                            <div class="text-sm text-blue-700 space-y-2">
                                <div id="zoho-help" class="hidden">
                                    <p><strong>Zoho Mail:</strong></p>
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li>Enable IMAP in your Zoho Mail settings</li>
                                        <li>Use your regular password or generate an app-specific password</li>
                                        <li>If you have 2FA enabled, you must use an app-specific password</li>
                                    </ul>
                                </div>
                                <div id="gmail-help" class="hidden">
                                    <p><strong>Gmail:</strong></p>
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li>Enable 2-factor authentication on your Google account</li>
                                        <li>Generate an app-specific password in your Google Account settings</li>
                                        <li>Use the app-specific password, not your regular password</li>
                                    </ul>
                                </div>
                                <div id="outlook-help" class="hidden">
                                    <p><strong>Outlook/Hotmail:</strong></p>
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li>Enable IMAP in your Outlook settings</li>
                                        <li>Use your regular password or app-specific password</li>
                                        <li>If you have 2FA enabled, use an app-specific password</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('accounts.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('provider').addEventListener('change', function() {
            // Hide all help sections
            document.querySelectorAll('[id$="-help"]').forEach(help => {
                help.classList.add('hidden');
            });
            
            // Show relevant help section
            const selectedProvider = this.value;
            if (selectedProvider) {
                document.getElementById(selectedProvider + '-help').classList.remove('hidden');
            }
        });

        // Show help for current provider
        const currentProvider = document.getElementById('provider').value;
        if (currentProvider) {
            document.getElementById(currentProvider + '-help').classList.remove('hidden');
        }

        function testConnection() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.classList.remove('hidden');
            resultsDiv.innerHTML = '<div class="text-yellow-600">Testing network connection...</div>';

            // Get form data
            const formData = new FormData();
            formData.append('provider', document.getElementById('provider').value);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

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
    </script>
</x-app-layout>
