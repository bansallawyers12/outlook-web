<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="emailApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex gap-3 items-center">
                            <div>
                                <label for="accountDropdown" class="sr-only">Account</label>
                                <select id="accountDropdown" x-model="selectedAccountId" @change="loadEmails" class="rounded-md border-gray-300 text-sm">
                                    <option value="">Select an account</option>
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
                            <div class="flex gap-2 items-center">
                                <div>
                                    <label for="startDate" class="sr-only">Start Date</label>
                                    <input type="date" id="startDate" x-model="startDate" class="rounded-md border-gray-300 text-sm" placeholder="Start date">
                                </div>
                                <div>
                                    <label for="endDate" class="sr-only">End Date</label>
                                    <input type="date" id="endDate" x-model="endDate" class="rounded-md border-gray-300 text-sm" placeholder="End date">
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('accounts.index') }}" class="inline-flex items-center rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-600">Manage Accounts</a>
                            <button type="button" @click="showZohoForm = true" class="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500">Add Zoho account</button>
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
                            <div x-show="emails.length === 0 && selectedAccountId" class="p-4 text-sm text-gray-500">No emails to display.</div>
                            <div x-show="!selectedAccountId" class="p-4 text-sm text-gray-500">Please select an email account to view emails.</div>
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
        
        <!-- Zoho Account Form -->
        <x-zoho-account-form />
    </div>

    <script>
        function emailApp() {
            return {
                showZohoForm: false,
                isAddingAccount: false,
                zohoForm: {
                    email: '',
                    password: '',
                    remember: false
                },
                accounts: @json($emailAccounts),
                selectedAccountId: null,
                folders: ['Inbox', 'Sent', 'Drafts', 'Trash', 'Spam'],
                selectedFolder: 'Inbox',
                startDate: '',
                endDate: '',
                emails: [],
                selectedEmail: null,
                loadEmails() {
                    if (!this.selectedAccountId) {
                        this.emails = [];
                        return;
                    }
                    
                    fetch(`/emails/sync/${this.selectedAccountId}?folder=${this.selectedFolder}&limit=50`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.emails = data.emails;
                            } else {
                                console.error('Error loading emails:', data.message);
                                this.emails = [];
                            }
                        })
                        .catch(error => {
                            console.error('Error loading emails:', error);
                            this.emails = [];
                        });
                    
                    this.selectedEmail = null;
                },
                syncEmails() {
                    if (!this.selectedAccountId) {
                        alert('Please select an email account first.');
                        return;
                    }
                    
                    // Validate date range
                    if (this.startDate && this.endDate && this.startDate > this.endDate) {
                        alert('Start date cannot be after end date.');
                        return;
                    }
                    
                    // Show loading state
                    const syncButton = document.querySelector('button[\\@click="syncEmails"]');
                    const originalText = syncButton.textContent;
                    syncButton.textContent = 'Syncing...';
                    syncButton.disabled = true;
                    
                    // Build query parameters
                    let queryParams = `folder=${this.selectedFolder}&limit=100`;
                    if (this.startDate) {
                        queryParams += `&start_date=${this.startDate}`;
                    }
                    if (this.endDate) {
                        queryParams += `&end_date=${this.endDate}`;
                    }
                    
                    fetch(`/emails/sync/${this.selectedAccountId}?${queryParams}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.emails = data.emails;
                            alert(data.message);
                        } else {
                            alert('Error syncing emails: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error syncing emails:', error);
                        alert('An error occurred while syncing emails.');
                    })
                    .finally(() => {
                        syncButton.textContent = originalText;
                        syncButton.disabled = false;
                    });
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
                },
                async addZohoAccount() {
                    this.isAddingAccount = true;
                    
                    try {
                        const response = await fetch('/auth/zoho/add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.zohoForm)
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Add the new account to the accounts list
                            this.accounts.push({
                                id: data.account.id,
                                label: `Zoho - ${data.account.email}`
                            });
                            
                            // Reset form and close modal
                            this.zohoForm = { email: '', password: '', remember: false };
                            this.showZohoForm = false;
                            
                            // Show success message
                            alert(data.message);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error adding Zoho account:', error);
                        alert('An error occurred while adding the account.');
                    } finally {
                        this.isAddingAccount = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>
