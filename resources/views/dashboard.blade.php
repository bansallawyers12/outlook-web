<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="emailApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex gap-3 items-center">
                            <div>
                                <label for="accountDropdown" class="sr-only">Account</label>
                                <select id="accountDropdown" x-model="selectedAccountId" @change="loadEmails" class="rounded-md border-gray-300 text-sm">
                                    <template x-for="acct in accounts" :key="acct.id">
                                        <option :value="acct.id" x-text="acct.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label for="folderDropdown" class="sr-only">Folder</label>
                                <select id="folderDropdown" x-model="selectedFolder" @change="loadEmails" class="rounded-md border-gray-300 text-sm">
                                    <template x-for="f in folders" :key="f">
                                        <option :value="f" x-text="f"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('oauth.redirect', ['provider' => 'zoho']) }}" class="inline-flex items-center rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-600">Add Zoho account</a>
                            <button type="button" @click="syncEmails" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Sync</button>
                            <button type="button" @click="composeMail" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">Compose</button>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 h-[70vh]">
                    <div class="md:col-span-1 border-r border-gray-200 overflow-y-auto">
                        <div class="divide-y divide-gray-100">
                            <template x-for="email in emails" :key="email.id">
                                <button type="button"
                                        class="w-full text-left px-4 py-3 hover:bg-gray-50 focus:bg-gray-50"
                                        :class="{ 'bg-gray-50': selectedEmail && selectedEmail.id === email.id }"
                                        @click="selectedEmail = email">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900" x-text="email.from"></p>
                                            <p class="mt-0.5 text-sm text-gray-600 truncate" x-text="email.subject"></p>
                                        </div>
                                        <span class="shrink-0 text-xs text-gray-500" x-text="email.time"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 line-clamp-2" x-text="email.snippet"></p>
                                </button>
                            </template>
                            <div x-show="emails.length === 0" class="p-4 text-sm text-gray-500">No emails to display.</div>
                        </div>
                    </div>
                    <div class="md:col-span-2 h-full overflow-y-auto">
                        <template x-if="selectedEmail">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900" x-text="selectedEmail.subject"></h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            <span class="font-medium" x-text="selectedEmail.from"></span>
                                            <span class="mx-1">·</span>
                                            <span x-text="selectedEmail.time"></span>
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button class="rounded-md border px-2 py-1 text-sm hover:bg-gray-50" @click="reply(selectedEmail)">Reply</button>
                                        <button class="rounded-md border px-2 py-1 text-sm hover:bg-gray-50" @click="forward(selectedEmail)">Forward</button>
                                        <button class="rounded-md border px-2 py-1 text-sm hover:bg-gray-50" @click="deleteEmail(selectedEmail)">Delete</button>
                                    </div>
                                </div>
                                <div class="mt-4 prose max-w-none">
                                    <p x-html="selectedEmail.body"></p>
                                </div>
                            </div>
                        </template>
                        <div x-show="!selectedEmail" class="h-full grid place-items-center text-gray-500">
                            <p class="text-sm">Select an email to view</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function emailApp() {
            return {
                accounts: [
                    { id: 1, label: 'Zoho - sales@acme.com' },
                ],
                selectedAccountId: 1,
                folders: ['Inbox', 'Sent', 'Drafts', 'Trash', 'Spam'],
                selectedFolder: 'Inbox',
                emails: [
                    {
                        id: 101,
                        from: 'alice@example.com',
                        subject: 'Welcome to the team!',
                        time: '09:20',
                        snippet: 'Hi John, great to have you on board. Let\'s catch up... ',
                        body: '<p>Hi John,<br/>Great to have you on board.<br/><br/>Regards,<br/>Alice</p>'
                    },
                    {
                        id: 102,
                        from: 'github@notifications.com',
                        subject: 'Build passed on main',
                        time: '08:05',
                        snippet: 'Your CI workflow run completed successfully.',
                        body: '<p>Your CI workflow run completed successfully.</p>'
                    }
                ],
                selectedEmail: null,
                loadEmails() {
                    // TODO: Fetch emails for selected account/folder via API
                    this.selectedEmail = null;
                },
                syncEmails() {
                    // TODO: Trigger backend sync route then refresh list
                },
                composeMail() {
                    // TODO: Open compose modal/route
                },
                reply(email) {
                    // TODO: Open reply composer with quoted content
                },
                forward(email) {
                    // TODO: Open forward composer
                },
                deleteEmail(email) {
                    // TODO: Call delete endpoint then remove from list
                }
            }
        }
    </script>
</x-app-layout>
