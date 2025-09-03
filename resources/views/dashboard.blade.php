<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <style>
        /* Email content styling */
        .email-content {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        
        .email-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 1rem 0;
        }
        
        .email-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        .email-content table td,
        .email-content table th {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .email-content blockquote {
            border-left: 4px solid #d1d5db;
            padding-left: 1rem;
            margin: 1rem 0;
            font-style: italic;
            color: #6b7280;
        }
        
        .email-content a {
            color: #2563eb;
            text-decoration: underline;
        }
        
        .email-content a:hover {
            color: #1d4ed8;
        }
        
        .email-content ul,
        .email-content ol {
            padding-left: 1.5rem;
            margin: 1rem 0;
        }
        
        .email-content li {
            margin: 0.25rem 0;
        }
        
        .email-content h1,
        .email-content h2,
        .email-content h3,
        .email-content h4,
        .email-content h5,
        .email-content h6 {
            font-weight: 600;
            margin: 1.5rem 0 0.5rem 0;
            color: #111827;
        }
        
        .email-content h1 { font-size: 1.875rem; }
        .email-content h2 { font-size: 1.5rem; }
        .email-content h3 { font-size: 1.25rem; }
        .email-content h4 { font-size: 1.125rem; }
        .email-content h5 { font-size: 1rem; }
        .email-content h6 { font-size: 0.875rem; }
        
        .email-content p {
            margin: 1rem 0;
        }
        
        .email-content strong,
        .email-content b {
            font-weight: 600;
        }
        
        .email-content em,
        .email-content i {
            font-style: italic;
        }
        
        .email-content code {
            background-color: #f3f4f6;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }
        
        .email-content pre {
            background-color: #f3f4f6;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
        
        .email-content pre code {
            background: none;
            padding: 0;
        }
        
        /* Handle email signatures */
        .email-content .signature {
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        /* Handle quoted content */
        .email-content .quoted {
            background-color: #f9fafb;
            border-left: 3px solid #d1d5db;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>

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

            <div class="bg-white border border-gray-200 sm:rounded-lg">
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
                                <div class="hidden md:block">
                                    <label for="searchQuery" class="sr-only">Search</label>
                                    <input type="text" id="searchQuery" x-model="searchQuery" @keydown.enter.prevent="loadEmails" class="rounded-md border-gray-300 text-sm w-64" placeholder="Search subject, from, body...">
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="syncEmails" class="inline-flex items-center rounded-md border border-blue-600 text-blue-700 bg-white px-3 py-2 text-sm font-semibold hover:bg-blue-50">Sync</button>
                            <button type="button" @click="syncAllFolders" class="inline-flex items-center rounded-md border border-indigo-600 text-indigo-700 bg-white px-3 py-2 text-sm font-semibold hover:bg-indigo-50">Sync All</button>
                            <button type="button" @click="openCompose()" class="inline-flex items-center rounded-md border border-emerald-600 text-emerald-700 bg-white px-3 py-2 text-sm font-semibold hover:bg-emerald-50">Compose</button>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 h-[70vh]">
                    <div class="md:col-span-1 border-r border-gray-200 overflow-y-auto">
                        <div>
                            <template x-for="group in groupedEmails" :key="group.date">
                                <div>
                                    <!-- Date Header -->
                                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                        <h3 class="text-sm font-medium text-gray-700" x-text="group.dateLabel"></h3>
                                    </div>
                                    
                                    <!-- Emails for this date -->
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="email in group.emails" :key="email.id">
                                            <button type="button"
                                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 transition-colors"
                                                    :class="{ 'bg-blue-50 border-l-4 border-blue-500': selectedEmail && selectedEmail.id === email.id }"
                                                    @click="selectedEmail = email">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="email.from"></p>
                                                            <div class="flex items-center gap-1">
                                                                <!-- Attachment icon -->
                                                                <svg x-show="email.hasAttachment" class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <!-- Flag icon -->
                                                                <svg x-show="email.isFlagged" class="w-3 h-3 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <p class="mt-0.5 text-sm text-gray-900 truncate" x-text="email.subject"></p>
                                                        <p class="mt-1 text-xs text-gray-500 line-clamp-2" x-text="email.snippet"></p>
                                                    </div>
                                                    <div class="flex flex-col items-end gap-1">
                                                        <span class="shrink-0 text-xs text-gray-500" x-text="email.time"></span>
                                                        <div class="flex items-center gap-1">
                                                            <!-- Trash icon -->
                                                            <button @click.stop="deleteEmail(email)" class="p-1 hover:bg-gray-200 rounded">
                                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div x-show="emails.length === 0 && selectedAccountId" class="p-4 text-sm text-gray-500">No emails to display.</div>
                            <div x-show="!selectedAccountId" class="p-4 text-sm text-gray-500">Please select an email account to view emails.</div>
                        </div>
                    </div>
                    <div class="md:col-span-2 h-full overflow-y-auto bg-white">
                        <template x-if="selectedEmail">
                            <div class="h-full flex flex-col">
                                <!-- Email Header -->
                                <div class="border-b border-gray-200 p-6 bg-white">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h1 class="text-xl font-semibold text-gray-900 mb-2" x-text="selectedEmail.subject || 'No Subject'"></h1>
                                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium">From:</span>
                                                    <span x-text="selectedEmail.from"></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium">Date:</span>
                                                    <span x-text="selectedEmail.time"></span>
                                                </div>
                                            </div>
                                            <div x-show="selectedEmail.to" class="mt-2 text-sm text-gray-600">
                                                <span class="font-medium">To:</span>
                                                <span x-text="selectedEmail.to"></span>
                                            </div>
                                            <div x-show="selectedEmail.cc" class="mt-1 text-sm text-gray-600">
                                                <span class="font-medium">Cc:</span>
                                                <span x-text="selectedEmail.cc"></span>
                                            </div>
                                            <div x-show="selectedEmail.reply_to" class="mt-1 text-sm text-gray-600">
                                                <span class="font-medium">Reply-To:</span>
                                                <span x-text="selectedEmail.reply_to"></span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 ml-4">
                                            <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" @click="reply(selectedEmail)">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                </svg>
                                                Reply
                                            </button>
                                            <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" @click="forward(selectedEmail)">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                </svg>
                                                Forward
                                            </button>
                                            <button class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" @click="deleteEmail(selectedEmail)">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Email Content -->
                                <div class="flex-1 overflow-y-auto">
                                    <div class="p-6">
                                        <!-- Show loading state if email body is being processed -->
                                        <div x-show="!selectedEmail.body && !selectedEmail.htmlBody" class="flex items-center justify-center h-32">
                                            <div class="text-gray-500">Loading email content...</div>
                                        </div>
                                        
                                        <!-- HTML Email Content -->
                                        <div x-show="selectedEmail.htmlBody" 
                                             class="email-content prose prose-sm max-w-none"
                                             x-html="selectedEmail.htmlBody">
                                        </div>
                                        
                                        <!-- Plain Text Email Content -->
                                        <div x-show="selectedEmail.body && !selectedEmail.htmlBody" 
                                             class="email-content prose prose-sm max-w-none">
                                            <div class="whitespace-pre-wrap font-mono text-sm" x-text="selectedEmail.body"></div>
                                        </div>
                                        
                                        <!-- Raw headers toggle -->
                                        <details x-show="selectedEmail.headers" class="mt-4">
                                            <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800">Show original headers</summary>
                                            <pre class="mt-2 text-xs bg-gray-50 p-3 rounded overflow-x-auto" x-text="JSON.stringify(selectedEmail.headers, null, 2)"></pre>
                                        </details>

                                        <!-- No content message -->
                                        <div x-show="!selectedEmail.body && !selectedEmail.htmlBody" class="text-gray-500 text-center py-8">
                                            No content available for this email.
                                        </div>
                                        
                                        <!-- Attachments Section -->
                                        <div x-show="selectedEmail.attachments && selectedEmail.attachments.length > 0" class="mt-6 border-t border-gray-200 pt-6">
                                            <h3 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                                </svg>
                                                Attachments (<span x-text="selectedEmail.attachments.length"></span>)
                                            </h3>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                <template x-for="attachment in selectedEmail.attachments" :key="attachment.id || attachment.name">
                                                    <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                                        <div class="flex-shrink-0">
                                                            <!-- File type icon based on extension -->
                                                            <div class="w-8 h-8 flex items-center justify-center rounded bg-gray-100">
                                                                <svg x-show="attachment.type && attachment.type.startsWith('image/')" class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <svg x-show="attachment.type && attachment.type.includes('pdf')" class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <svg x-show="attachment.type && (attachment.type.includes('word') || attachment.type.includes('document'))" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <svg x-show="attachment.type && attachment.type.includes('excel')" class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <svg x-show="!attachment.type || (!attachment.type.startsWith('image/') && !attachment.type.includes('pdf') && !attachment.type.includes('word') && !attachment.type.includes('document') && !attachment.type.includes('excel'))" class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <div class="ml-3 flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="attachment.name || attachment.filename || 'Unknown file'"></p>
                                                            <p class="text-xs text-gray-500" x-text="attachment.size ? formatFileSize(attachment.size) : ''"></p>
                                                        </div>
                                                        <div class="flex items-center gap-1 ml-2">
                                                            <button @click="downloadAttachment(attachment)" class="p-1 text-gray-400 hover:text-gray-600" title="Download">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                </svg>
                                                            </button>
                                                            <button x-show="attachment.type && attachment.type.startsWith('image/')" @click="viewImage(attachment)" class="p-1 text-gray-400 hover:text-gray-600" title="View">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="!selectedEmail" class="h-full grid place-items-center text-gray-500">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No email selected</h3>
                                <p class="mt-1 text-sm text-gray-500">Choose an email from the list to view its contents.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Compose Modal (moved inside x-data scope) -->
        <div x-show="showCompose" class="fixed inset-0 z-50 flex items-center justify-center" style="display:none">
            <div class="absolute inset-0 bg-black bg-opacity-30" @click="showCompose = false"></div>
            <div class="relative bg-white w-full max-w-3xl mx-4 rounded-lg shadow-lg">
                <div class="border-b px-4 py-3 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Compose Email</h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="showCompose = false">✕</button>
                </div>
                <div class="p-4 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-700">From account</label>
                            <select x-model="selectedAccountId" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">Select an account</option>
                                <template x-for="acct in accounts" :key="acct.id">
                                    <option :value="acct.id" x-text="acct.label"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">To</label>
                            <input type="email" x-model="compose.to" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="recipient@example.com" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-700">Cc</label>
                            <input type="text" x-model="compose.cc" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Bcc</label>
                            <input type="text" x-model="compose.bcc" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Subject</label>
                        <input type="text" x-model="compose.subject" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Body</label>
                        <textarea rows="10" x-model="compose.body" class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                </div>
                <div class="border-t px-4 py-3 flex items-center justify-end gap-2">
                    <button type="button" class="inline-flex items-center rounded-md border border-gray-300 text-gray-700 bg-white px-3 py-2 text-sm font-semibold hover:bg-gray-50" @click="showCompose = false">Cancel</button>
                    <button type="button" class="inline-flex items-center rounded-md border border-emerald-600 text-white bg-emerald-600 px-3 py-2 text-sm font-semibold hover:bg-emerald-700" @click="sendEmail({ to: compose.to, subject: compose.subject, body: compose.body })">Send</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function emailApp() {
            return {
                accounts: @json($emailAccounts),
                selectedAccountId: null,
                folders: ['Inbox', 'Sent', 'Drafts', 'Trash', 'Spam'],
                selectedFolder: 'Inbox',
                searchQuery: '',
                startDate: '',
                endDate: '',
                emails: [],
                selectedEmail: null,
                get groupedEmails() {
                    if (!this.emails || this.emails.length === 0) return [];
                    
                    // Group emails by date
                    const groups = {};
                    this.emails.forEach(email => {
                        let date;
                        try {
                            date = new Date(email.date || email.created_at);
                            if (isNaN(date.getTime())) {
                                date = new Date(); // fallback to current date
                            }
                        } catch (e) {
                            date = new Date(); // fallback to current date
                        }
                        
                        const dateKey = date.toDateString();
                        
                        if (!groups[dateKey]) {
                            groups[dateKey] = {
                                date: dateKey,
                                dateLabel: this.formatDateLabel(date),
                                emails: []
                            };
                        }
                        groups[dateKey].emails.push(email);
                    });
                    
                    // Convert to array and sort by date (newest first)
                    return Object.values(groups).sort((a, b) => new Date(b.date) - new Date(a.date));
                },
                loadEmails() {
                    if (!this.selectedAccountId) {
                        this.emails = [];
                        return;
                    }
                    
                    const params = new URLSearchParams({ folder: this.selectedFolder, limit: 50 });
                    if (this.startDate) params.set('start_date', this.startDate);
                    if (this.endDate) params.set('end_date', this.endDate);
                    if (this.searchQuery) params.set('q', this.searchQuery);
                    fetch(`/emails/sync/${this.selectedAccountId}?${params.toString()}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.emails = this.processEmails(data.emails);
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
                    if (this.searchQuery) {
                        queryParams += `&q=${encodeURIComponent(this.searchQuery)}`;
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
                            this.emails = this.processEmails(data.emails);
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
                syncAllFolders() {
                    if (!this.selectedAccountId) {
                        alert('Please select an email account first.');
                        return;
                    }
                    if (this.startDate && this.endDate && this.startDate > this.endDate) {
                        alert('Start date cannot be after end date.');
                        return;
                    }
                    const btn = event.currentTarget;
                    const original = btn.textContent;
                    btn.textContent = 'Syncing All...';
                    btn.disabled = true;
                    let queryParams = `folder=all&limit=100`;
                    if (this.startDate) queryParams += `&start_date=${this.startDate}`;
                    if (this.endDate) queryParams += `&end_date=${this.endDate}`;
                    if (this.searchQuery) queryParams += `&q=${encodeURIComponent(this.searchQuery)}`;
                    fetch(`/emails/sync/${this.selectedAccountId}?${queryParams}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            this.loadEmails();
                            alert(res.message || 'Sync complete.');
                        } else {
                            alert('Sync All failed: ' + (res.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('An error occurred while syncing all folders.');
                    })
                    .finally(() => {
                        btn.textContent = original;
                        btn.disabled = false;
                    });
                },
                openCompose(prefill = {}) {
                    if (!this.selectedAccountId) {
                        alert('Please select an email account first.');
                        return;
                    }
                    this.compose.to = prefill.to || '';
                    this.compose.cc = prefill.cc || '';
                    this.compose.bcc = prefill.bcc || '';
                    this.compose.subject = prefill.subject || '';
                    this.compose.body = prefill.body || '';
                    this.showCompose = true;
                },
                reply(email) {
                    if (!this.selectedAccountId) {
                        alert('Please select an email account first.');
                        return;
                    }
                    if (!email) {
                        alert('Select an email to reply to.');
                        return;
                    }
                    const subject = (email.subject && email.subject.startsWith('Re:')) ? email.subject : `Re: ${email.subject || ''}`;
                    const quoted = `\n\n---- Original message ----\nFrom: ${email.from}\nDate: ${email.time}\nSubject: ${email.subject}\n\n` + (email.body || '');
                    this.openCompose({ to: email.from || '', subject, body: quoted });
                },
                forward(email) {
                    if (!this.selectedAccountId) {
                        alert('Please select an email account first.');
                        return;
                    }
                    if (!email) {
                        alert('Select an email to forward.');
                        return;
                    }
                    const subject = (email.subject && email.subject.startsWith('Fwd:')) ? email.subject : `Fwd: ${email.subject || ''}`;
                    const quoted = `\n\n---- Forwarded message ----\nFrom: ${email.from}\nDate: ${email.time}\nSubject: ${email.subject}\n\n` + (email.body || '');
                    this.openCompose({ subject, body: quoted });
                },
                showCompose: false,
                compose: { to: '', cc: '', bcc: '', subject: '', body: '' },
                deleteEmail(email) {
                    // TODO: Call delete endpoint then remove from list
                },
                formatDateLabel(date) {
                    if (!date || isNaN(date.getTime())) {
                        return 'Unknown Date';
                    }
                    
                    const today = new Date();
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    
                    if (date.toDateString() === today.toDateString()) {
                        return 'Today';
                    } else if (date.toDateString() === yesterday.toDateString()) {
                        return 'Yesterday';
                    } else {
                        return date.toLocaleDateString('en-US', { 
                            weekday: 'short', 
                            month: 'short', 
                            day: 'numeric' 
                        });
                    }
                },
                processEmails(emails) {
                    return emails.map(email => {
                        // Prefer explicit HTML field if provided by backend
                        let rawBody = email.body || email.text_body || '';
                        const explicitHtml = email.html_body || email.htmlBody || null;

                        // Heuristic: detect if body itself is HTML
                        const looksLikeHtml = /<\s*html[\s>]|<body[\s>]|<div[\s>]|<p[\s>]|<br\s*\/?>(?=\s|$)|<table[\s>]/i.test(rawBody);
                        const htmlContent = explicitHtml || (looksLikeHtml ? rawBody : null);
                        const textContent = explicitHtml ? rawBody : (looksLikeHtml ? null : rawBody);

                        // Create snippet (strip HTML when needed)
                        let snippet = '';
                        if (email.snippet) {
                            snippet = email.snippet;
                        } else if (textContent) {
                            snippet = (textContent || '').substring(0, 100) + (textContent && textContent.length > 100 ? '...' : '');
                        } else if (htmlContent) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = htmlContent;
                            const plain = (tempDiv.textContent || tempDiv.innerText || '').trim();
                            snippet = plain.substring(0, 100) + (plain.length > 100 ? '...' : '');
                        }

                        return {
                            ...email,
                            hasAttachment: email.hasAttachment || email.has_attachment || (email.attachments && email.attachments.length > 0) || false,
                            isFlagged: email.isFlagged || email.is_flagged || false,
                            time: this.formatTime(email.date || email.created_at),
                            snippet: snippet,
                            htmlBody: htmlContent,
                            body: textContent,
                            to: email.to || email.to_email || '',
                            attachments: email.attachments || []
                        };
                    });
                },
                formatTime(dateString) {
                    if (!dateString) return 'Unknown time';
                    
                    let date;
                    try {
                        date = new Date(dateString);
                        if (isNaN(date.getTime())) {
                            return 'Invalid date';
                        }
                    } catch (e) {
                        return 'Invalid date';
                    }
                    
                    const now = new Date();
                    const diffInHours = (now - date) / (1000 * 60 * 60);
                    
                    if (diffInHours < 24) {
                        return date.toLocaleTimeString('en-US', { 
                            hour: 'numeric', 
                            minute: '2-digit',
                            hour12: true 
                        });
                    } else {
                        return date.toLocaleDateString('en-US', { 
                            month: 'short', 
                            day: 'numeric' 
                        });
                    }
                },
                formatFileSize(bytes) {
                    if (!bytes) return '';
                    
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    if (bytes === 0) return '0 Bytes';
                    
                    const i = Math.floor(Math.log(bytes) / Math.log(1024));
                    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
                },
                downloadAttachment(attachment) {
                    // TODO: Implement attachment download
                    console.log('Download attachment:', attachment);
                    alert('Download functionality will be implemented');
                },
                viewImage(attachment) {
                    // TODO: Implement image viewer
                    console.log('View image:', attachment);
                    alert('Image viewer will be implemented');
                },
                sendEmail({ to, subject, body }) {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/emails/send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        credentials: 'same-origin',
                        mode: 'same-origin',
                        body: JSON.stringify({
                            account_id: this.selectedAccountId,
                            to,
                            subject,
                            body
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.ok) {
                            alert('Email sent successfully.');
                            this.showCompose = false;
                            this.compose = { to: '', cc: '', bcc: '', subject: '', body: '' };
                        } else {
                            alert('Failed to send email: ' + (res.error || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('An error occurred while sending email.');
                    });
                }

            }
        }
    </script>
</x-app-layout>
