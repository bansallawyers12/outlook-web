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
                            <label class="block text-sm font-medium text-gray-700">Body</label>
                            <textarea id="body" rows="12" class="mt-1 block w-full rounded-md border-gray-300">{{ $body }}</textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <x-secondary-button type="button" onclick="window.close()">Cancel</x-secondary-button>
                            <x-primary-button type="submit">Send</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function submitCompose(e) {
            e.preventDefault();
            const accountId = document.getElementById('accountSelect').value || document.getElementById('account_id').value;
            if (!accountId) { alert('Please choose an account.'); return false; }
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = new URL('{{ route('emails.send') }}', window.location.origin).toString();
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'ngrok-skip-browser-warning': '1'
                },
                credentials: 'same-origin',
                mode: 'same-origin',
                body: JSON.stringify({
                    account_id: accountId,
                    to: document.getElementById('to').value,
                    subject: document.getElementById('subject').value,
                    body: document.getElementById('body').value
                })
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


