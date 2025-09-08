<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Compose Email') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form id="composeForm" class="space-y-4" onsubmit="return submitCompose(event)">
                        <input type="hidden" name="account_id" id="account_id" value="{{ $accountId ?? '' }}" />

                        <div>
                            <label class="block text-sm font-medium text-gray-700">From account</label>
                            <select id="accountSelect" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">Select account</option>
                                @foreach(\App\Models\EmailAccount::where('user_id', auth()->id())->get() as $acct)
                                    <option value="{{ $acct->id }}" {{ (isset($accountId) && $accountId == $acct->id) ? 'selected' : '' }}>
                                        {{ ucfirst($acct->provider) }} - {{ $acct->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">To</label>
                            <input type="email" id="to" class="mt-1 block w-full rounded-md border-gray-300" value="{{ $to }}" required />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cc</label>
                                <input type="text" id="cc" class="mt-1 block w-full rounded-md border-gray-300" value="{{ $cc }}" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bcc</label>
                                <input type="text" id="bcc" class="mt-1 block w-full rounded-md border-gray-300" value="{{ $bcc }}" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subject</label>
                            <input type="text" id="subject" class="mt-1 block w-full rounded-md border-gray-300" value="{{ $subject }}" />
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">Body</label>
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm text-gray-600">Signature:</label>
                                    <select id="signatureSelect" class="text-sm rounded-md border-gray-300">
                                        <option value="">No signature</option>
                                        @foreach($signatures as $signature)
                                            <option value="{{ $signature->id }}" 
                                                    data-content="{{ $signature->content }}"
                                                    data-html-content="{{ $signature->html_content }}"
                                                    {{ $signature->is_default ? 'selected' : '' }}>
                                                {{ $signature->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('signatures.index', ['account_id' => $accountId]) }}" 
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        Manage
                                    </a>
                                </div>
                            </div>
                            <textarea id="body" rows="12" class="mt-1 block w-full rounded-md border-gray-300">{{ $body }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attachments</label>
                            <div class="mt-1">
                                <input type="file" id="attachments" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="*/*">
                                <div id="attachment-list" class="mt-2 space-y-2"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="saveDraft()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                                    Save Draft
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-secondary-button type="button" onclick="window.close()">Cancel</x-secondary-button>
                                <x-primary-button type="submit">Send</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedFiles = [];

        // Handle file selection
        document.getElementById('attachments').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            selectedFiles = files;
            displaySelectedFiles();
        });

        // Handle signature selection
        document.getElementById('signatureSelect').addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const bodyTextarea = document.getElementById('body');
            
            if (selectedOption.value) {
                const signatureContent = selectedOption.dataset.content || '';
                const currentBody = bodyTextarea.value;
                
                // Remove any existing signature (look for common signature patterns)
                const bodyWithoutSignature = currentBody.replace(/\n\n--\s*\n.*$/s, '').replace(/\n\nKind regards.*$/s, '').replace(/\n\nBest regards.*$/s, '').replace(/\n\nSincerely.*$/s, '');
                
                // Add the new signature
                const newBody = bodyWithoutSignature + (bodyWithoutSignature ? '\n\n' : '') + signatureContent;
                bodyTextarea.value = newBody;
            } else {
                // Remove signature if "No signature" is selected
                const currentBody = bodyTextarea.value;
                const bodyWithoutSignature = currentBody.replace(/\n\n--\s*\n.*$/s, '').replace(/\n\nKind regards.*$/s, '').replace(/\n\nBest regards.*$/s, '').replace(/\n\nSincerely.*$/s, '');
                bodyTextarea.value = bodyWithoutSignature;
            }
        });

        function displaySelectedFiles() {
            const attachmentList = document.getElementById('attachment-list');
            attachmentList.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded border';
                fileItem.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-700">${file.name}</span>
                        <span class="text-xs text-gray-500 ml-2">(${formatFileSize(file.size)})</span>
                    </div>
                    <button type="button" onclick="removeFile(${index})" class="text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                `;
                attachmentList.appendChild(fileItem);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            displaySelectedFiles();
            // Update the file input
            const fileInput = document.getElementById('attachments');
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function saveDraft() {
            const accountId = document.getElementById('accountSelect').value || document.getElementById('account_id').value;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = new URL('{{ route('emails.save-draft') }}', window.location.origin).toString();
            
            const formData = new FormData();
            formData.append('account_id', accountId || '');
            formData.append('to', document.getElementById('to').value);
            formData.append('subject', document.getElementById('subject').value);
            formData.append('body', document.getElementById('body').value);
            formData.append('cc', document.getElementById('cc').value);
            formData.append('bcc', document.getElementById('bcc').value);
            
            // Add attachment metadata (not the actual files for drafts)
            const attachmentMetadata = selectedFiles.map(file => ({
                name: file.name,
                size: file.size,
                type: file.type
            }));
            formData.append('attachments', JSON.stringify(attachmentMetadata));

            fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'ngrok-skip-browser-warning': '1'
                },
                credentials: 'same-origin',
                mode: 'same-origin',
                body: formData
            }).then(r => r.json()).then(res => {
                if (res.success) {
                    alert('Draft saved successfully!');
                } else {
                    alert('Failed to save draft: ' + (res.message || 'Unknown error'));
                }
            }).catch(err => {
                console.error('Draft save failed', err);
                alert('Error saving draft: ' + (err && err.message ? err.message : err));
            });
        }

        function submitCompose(e) {
            e.preventDefault();
            const accountId = document.getElementById('accountSelect').value || document.getElementById('account_id').value;
            if (!accountId) { alert('Please choose an account.'); return false; }
            
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = new URL('{{ route('emails.send') }}', window.location.origin).toString();
            
            // Create FormData for file uploads
            const formData = new FormData();
            formData.append('account_id', accountId);
            formData.append('to', document.getElementById('to').value);
            formData.append('subject', document.getElementById('subject').value);
            formData.append('body', document.getElementById('body').value);
            formData.append('cc', document.getElementById('cc').value);
            formData.append('bcc', document.getElementById('bcc').value);
            
            // Add attachments
            selectedFiles.forEach((file, index) => {
                formData.append(`attachments[${index}]`, file);
            });

            fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'ngrok-skip-browser-warning': '1'
                },
                credentials: 'same-origin',
                mode: 'same-origin',
                body: formData
            }).then(r => r.json()).then(res => {
                if (res.ok) {
                    alert('Email sent.');
                    window.close();
                } else {
                    alert('Failed: ' + (res.error || 'Unknown error'));
                }
            }).catch(err => {
                console.error('Compose send fetch failed', err);
                alert('Error: ' + (err && err.message ? err.message : err));
            });
            return false;
        }
    </script>
</x-app-layout>


