<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <style>
        /* Hide elements with x-cloak until Alpine.js loads */
        [x-cloak] { display: none; }
        
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

    <div class="py-6" x-data="emailApp()" x-init="init()">
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

            <div class="bg-white border border-gray-200 sm:rounded-lg shadow-sm">
                <!-- Main Filter Bar -->
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Left Side - Primary Filters -->
                        <div class="flex flex-col sm:flex-row gap-3 flex-1">
                            <!-- Account Selector -->
                            <div class="min-w-0 flex-1 sm:max-w-xs">
                                <label for="accountDropdown" class="block text-xs font-medium text-gray-700 mb-1">Account</label>
                                <select id="accountDropdown" x-model="selectedAccountId" @change="loadEmails" 
                                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select an account</option>
                                    <template x-for="acct in accounts" :key="acct.id">
                                        <option :value="acct.id" x-text="acct.label"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Folder Selector -->
                            <div class="min-w-0 flex-1 sm:max-w-xs">
                                <label for="folderDropdown" class="block text-xs font-medium text-gray-700 mb-1">Folder</label>
                                <select id="folderDropdown" x-model="selectedFolder" @change="loadEmails" 
                                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <template x-for="f in folders" :key="f">
                                        <option :value="f" x-text="f"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Search Bar -->
                            <div class="min-w-0 flex-1 sm:max-w-md">
                                <label for="searchQuery" class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                </div>
                                    <input type="text" id="searchQuery" x-model="searchQuery" 
                                           @keydown.enter.prevent="loadEmails" 
                                           @input="updateSearchSuggestions()"
                                           @focus="showSuggestions = true"
                                           @blur="setTimeout(() => showSuggestions = false, 200)"
                                           class="w-full pl-10 pr-20 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           placeholder="Search emails...">
                                    
                                    <!-- Search Suggestions Dropdown -->
                                    <div x-show="showSuggestions && (searchSuggestions.length > 0 || recentSearches.length > 0)" 
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        
                                        <!-- Recent Searches -->
                                        <div x-show="recentSearches.length > 0" class="p-2">
                                            <div class="text-xs font-medium text-gray-500 mb-1">Recent Searches</div>
                                            <template x-for="search in recentSearches.slice(0, 3)" :key="search">
                                                <button @click="applySuggestion(search)" 
                                                        class="w-full text-left px-2 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <span x-text="search"></span>
                                                </button>
                                            </template>
                                </div>
                                        
                                        <!-- Search Suggestions -->
                                        <div x-show="searchSuggestions.length > 0" class="p-2 border-t border-gray-200">
                                            <div class="text-xs font-medium text-gray-500 mb-1">Suggestions</div>
                                            <template x-for="suggestion in searchSuggestions" :key="suggestion">
                                                <button @click="applySuggestion(suggestion)" 
                                                        class="w-full text-left px-2 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <span x-text="suggestion"></span>
                                                </button>
                                            </template>
                                </div>
                            </div>
                                    
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <button x-show="searchQuery" @click="searchQuery = ''; loadEmails()" 
                                                class="p-1 text-gray-400 hover:text-gray-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                        <div class="border-l border-gray-300 mx-2 h-4"></div>
                                        <button @click="saveSearch()" 
                                                class="p-1 text-gray-400 hover:text-gray-600" 
                                                title="Save search">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                            </svg>
                                        </button>
                        </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Action Buttons -->
                        <div class="flex gap-2 flex-shrink-0 flex-wrap">
                            <!-- Quick Date Range Selector -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-medium text-gray-700">Sync Date:</label>
                                <select x-model="syncDateRange" @change="setSyncDateRange" 
                                        class="rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                                <!-- Date Range Indicator -->
                                <div x-show="startDate || endDate" class="flex items-center gap-1 text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span x-text="startDate && endDate ? `${startDate} to ${endDate}` : (startDate ? `From ${startDate}` : `Until ${endDate}`)"></span>
                                    <button @click="clearDateRange" class="ml-1 text-blue-400 hover:text-blue-600">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="button" @click="toggleAdvancedFilters" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filters
                            </button>
                            <button type="button" @click="syncEmails" 
                                    class="inline-flex items-center px-3 py-2 border border-blue-600 text-blue-700 bg-white rounded-lg text-sm font-medium hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Sync
                            </button>
                            <button type="button" @click="syncAllFolders" 
                                    class="inline-flex items-center px-3 py-2 border border-indigo-600 text-indigo-700 bg-white rounded-lg text-sm font-medium hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Sync All
                            </button>
                            <button type="button" @click="openCompose()" 
                                    class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Compose
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div x-show="selectedEmails.length > 0" x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 transform -translate-y-1" 
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 transform translate-y-0" 
                     x-transition:leave-end="opacity-0 transform -translate-y-1"
                     class="border-b border-gray-200 bg-blue-50 p-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-700">
                                <span x-text="selectedEmails.length"></span> selected
                            </span>
                            <button @click="selectAllEmails()" class="text-sm text-blue-600 hover:text-blue-800">
                                Select All
                            </button>
                            <button @click="clearSelection()" class="text-sm text-gray-600 hover:text-gray-800">
                                Clear
                            </button>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <button @click="bulkMarkAsRead()" 
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">
                                Mark as Read
                            </button>
                            <button @click="bulkMarkAsUnread()" 
                                    class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white rounded text-sm font-medium hover:bg-gray-700">
                                Mark as Unread
                            </button>
                            <button @click="bulkFlag()" 
                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-600 text-white rounded text-sm font-medium hover:bg-yellow-700">
                                Flag
                            </button>
                            <button @click="bulkDelete()" 
                                    class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded text-sm font-medium hover:bg-red-700">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters Panel -->
                <div x-show="showAdvancedFilters" x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 transform -translate-y-1" 
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 transform translate-y-0" 
                     x-transition:leave-end="opacity-0 transform -translate-y-1"
                     class="border-b border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Date Range -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700">Date Range</label>
                        <div class="flex gap-2">
                                <input type="date" x-model="startDate" 
                                       class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       placeholder="Start date">
                                <input type="date" x-model="endDate" 
                                       class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       placeholder="End date">
                        </div>
                        </div>

                        <!-- Search Fields -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700">Search In</label>
                            <div class="space-y-1">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="searchFields.from" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">From</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="searchFields.to" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">To</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="searchFields.subject" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Subject</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="searchFields.body" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Body</span>
                                </label>
                            </div>
                        </div>

                        <!-- Email Filters -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700">Filters</label>
                            <div class="space-y-1">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="filters.hasAttachments" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Has Attachments</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="filters.isUnread" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Unread Only</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="filters.isFlagged" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Flagged</span>
                                </label>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700">Quick Actions</label>
                            <div class="space-y-1">
                                <button @click="applyQuickFilter('today')" 
                                        class="block w-full text-left px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    Today
                                </button>
                                <button @click="applyQuickFilter('week')" 
                                        class="block w-full text-left px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    This Week
                                </button>
                                <button @click="applyQuickFilter('month')" 
                                        class="block w-full text-left px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    This Month
                                </button>
                                <button @click="clearAllFilters()" 
                                        class="block w-full text-left px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Apply Filters Button -->
                    <div class="mt-4 flex justify-end">
                        <button @click="applyFilters()" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Apply Filters
                        </button>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row h-[70vh]">
                    <!-- Email List Panel -->
                    <div class="lg:w-1/3 w-full border-r border-gray-200 overflow-y-auto bg-white">
                        <!-- Loading Indicator -->
                        <div x-show="isLoading" class="p-6 text-center">
                            <div class="inline-flex items-center px-4 py-2 text-sm text-gray-600">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading emails...
                            </div>
                        </div>
                        
                        <div x-show="!isLoading">
                            <template x-for="group in groupedEmails" :key="group.date">
                                <div>
                                    <!-- Date Header -->
                                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                        <h3 class="text-sm font-semibold text-gray-800" x-text="group.dateLabel"></h3>
                                    </div>
                                    
                                    <!-- Emails for this date -->
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="email in group.emails" :key="email.id">
                                            <div class="flex items-start gap-3 px-4 py-4 hover:bg-gray-50 transition-colors cursor-pointer group"
                                                 :class="{ 'bg-blue-50 border-l-4 border-blue-500': selectedEmail && selectedEmail.id === email.id }">
                                                <!-- Checkbox -->
                                                <input type="checkbox" 
                                                       :value="email.id" 
                                                       x-model="selectedEmails"
                                                       @click.stop
                                                       class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                
                                                <!-- Email Content -->
                                                <button type="button"
                                                        class="flex-1 text-left min-w-0"
                                                        @click="selectEmail(email)">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="flex-1 min-w-0">
                                                            <!-- Sender and Icons -->
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <p class="text-sm font-semibold truncate" 
                                                                   :class="email.is_read ? 'text-gray-600' : 'text-gray-900 font-bold'" 
                                                                   x-text="email.from"></p>
                                                                <div class="flex items-center gap-1 flex-shrink-0">
                                                                    <!-- Attachment icon -->
                                                                    <svg x-show="email.hasAttachment" class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    <!-- Flag icon -->
                                                                    <svg x-show="email.is_flagged" class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Subject -->
                                                            <p class="text-sm truncate mb-1" 
                                                               :class="email.is_read ? 'text-gray-600' : 'text-gray-900 font-semibold'" 
                                                               x-text="email.subject || '(No Subject)'"></p>
                                                            
                                                            <!-- Snippet -->
                                                            <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed" x-text="email.snippet"></p>
                                                        </div>
                                                        
                                                        <!-- Time and Actions -->
                                                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                                            <span class="text-xs text-gray-500" x-text="email.time"></span>
                                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <!-- Trash icon -->
                                                                <button @click.stop="deleteEmail(email)" 
                                                                        class="p-1.5 hover:bg-red-100 rounded-full transition-colors"
                                                                        title="Delete email">
                                                                    <svg class="w-4 h-4 text-gray-400 hover:text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Empty States -->
                            <div x-show="emails.length === 0 && selectedAccountId" class="p-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-sm text-gray-500">No emails to display.</p>
                            </div>
                            <div x-show="!selectedAccountId" class="p-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-sm text-gray-500">Please select an email account to view emails.</p>
                            </div>
                        </div>
                    </div>
                    <!-- Email Content Panel -->
                    <div class="lg:w-2/3 w-full h-full overflow-y-auto bg-white">
                        <template x-if="selectedEmail">
                            <div class="h-full flex flex-col">
                                <!-- Email Header -->
                                <div class="border-b border-gray-200 p-6 bg-white shadow-sm">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h1 class="text-2xl font-bold text-gray-900 mb-4 break-words" x-text="selectedEmail.subject || 'No Subject'"></h1>
                                            
                                            <!-- Email Metadata -->
                                            <div class="space-y-2 text-sm">
                                                <div class="flex items-center gap-3">
                                                    <span class="font-semibold text-gray-700 w-12">From:</span>
                                                    <span class="text-gray-900" x-text="selectedEmail.from"></span>
                                                </div>
                                                
                                                <div x-show="selectedEmail.to" class="flex items-center gap-3">
                                                    <span class="font-semibold text-gray-700 w-12">To:</span>
                                                    <span class="text-gray-900" x-text="selectedEmail.to"></span>
                                                </div>
                                                
                                                <div x-show="selectedEmail.cc" class="flex items-center gap-3">
                                                    <span class="font-semibold text-gray-700 w-12">Cc:</span>
                                                    <span class="text-gray-900" x-text="selectedEmail.cc"></span>
                                                </div>
                                                
                                                <div x-show="selectedEmail.reply_to" class="flex items-center gap-3">
                                                    <span class="font-semibold text-gray-700 w-12">Reply-To:</span>
                                                    <span class="text-gray-900" x-text="selectedEmail.reply_to"></span>
                                                </div>
                                                
                                                <div class="flex items-center gap-3">
                                                    <span class="font-semibold text-gray-700 w-12">Date:</span>
                                                    <span class="text-gray-900" x-text="selectedEmail.time"></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex gap-2 ml-6 flex-shrink-0">
                                            <button class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" @click="reply(selectedEmail)">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                </svg>
                                                Reply
                                            </button>
                                            <button class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" @click="forward(selectedEmail)">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                </svg>
                                                Forward
                                            </button>
                                            <button class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors" @click="deleteEmail(selectedEmail)">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Email Content -->
                                <div class="flex-1 overflow-y-auto bg-gray-50">
                                    <div class="p-8">
                                        <!-- Show loading state if email body is being processed -->
                                        <div x-show="(!selectedEmail.body && !selectedEmail.htmlBody) || selectedEmail.isLoadingContent" class="flex items-center justify-center h-32">
                                            <div class="flex items-center text-gray-500">
                                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span x-text="selectedEmail.isLoadingContent ? 'Loading from EML file...' : 'Loading email content...'"></span>
                                            </div>
                                        </div>
                                        
                                        <!-- HTML Email Content -->
                                        <div x-show="selectedEmail.htmlBody" 
                                             class="email-content prose prose-lg max-w-none bg-white rounded-lg shadow-sm p-6 border border-gray-200"
                                             x-html="selectedEmail.htmlBody">
                                        </div>
                                        
                                        <!-- Plain Text Email Content -->
                                        <div x-show="selectedEmail.body && !selectedEmail.htmlBody" 
                                             class="email-content prose prose-lg max-w-none bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                                            <div class="whitespace-pre-wrap text-gray-900 leading-relaxed" x-text="selectedEmail.body"></div>
                                        </div>
                                        
                                        <!-- Content source indicator -->
                                        <div x-show="selectedEmail.contentSource" class="mt-4 flex items-center text-xs text-gray-500">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span x-text="selectedEmail.contentSource === 'eml' ? 'Content loaded from EML file (S3)' : 'Content loaded from database'"></span>
                                        </div>

                                        <!-- Raw headers toggle -->
                                        <details x-show="selectedEmail.headers" class="mt-6">
                                            <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium bg-gray-100 px-3 py-2 rounded-lg">
                                                Show original headers
                                            </summary>
                                            <pre class="mt-3 text-xs bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto font-mono" x-text="JSON.stringify(selectedEmail.headers, null, 2)"></pre>
                                        </details>

                                        <!-- No content message -->
                                        <div x-show="!selectedEmail.body && !selectedEmail.htmlBody" class="text-center py-12">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p class="text-gray-500">No content available for this email.</p>
                                        </div>
                                        
                                        <!-- Attachments Section -->
                                        <div x-show="selectedEmail.attachments && selectedEmail.attachments.length > 0" class="mt-6 border-t border-gray-200 pt-6">
                                            <h3 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                                </svg>
                                                Attachments (<span x-text="selectedEmail.attachments.length"></span>)
                                            </h3>
                                            <div class="space-y-3">
                                                <template x-for="attachment in selectedEmail.attachments" :key="attachment.id || attachment.name">
                                                    <div class="attachment-item bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                                        <div class="flex items-start justify-between">
                                                            <div class="flex items-start space-x-3 flex-1">
                                                                <!-- File type icon -->
                                                                <div class="flex-shrink-0">
                                                                    <div x-show="attachment.type && attachment.type.startsWith('image/')" class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <div x-show="attachment.type && attachment.type.includes('pdf')" class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                                                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <div x-show="attachment.type && (attachment.type.includes('word') || attachment.type.includes('document'))" class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <div x-show="!attachment.type || (!attachment.type.startsWith('image/') && !attachment.type.includes('pdf') && !attachment.type.includes('word') && !attachment.type.includes('document'))" class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                                                        <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                </div>

                                                                <!-- File info -->
                                                                <div class="flex-1 min-w-0">
                                                                    <h4 class="text-sm font-medium text-gray-900 truncate" x-text="attachment.name || attachment.filename || 'Unknown file'"></h4>
                                                                    <p class="text-xs text-gray-500 mt-1">
                                                                        <span x-text="attachment.size ? formatFileSize(attachment.size) : ''"></span>
                                                                        <span x-show="attachment.type" x-text="' • ' + attachment.type"></span>
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <!-- Action buttons -->
                                                            <div class="flex items-center space-x-2 ml-4">
                                                                <button 
                                                                    x-show="attachment.type && (attachment.type.startsWith('image/') || attachment.type.includes('pdf'))"
                                                                    @click="viewAttachment(attachment)"
                                                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                                    title="Preview attachment"
                                                                >
                                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    View
                                                                </button>
                                                                
                                                                <button 
                                                                    @click="downloadAttachment(attachment)"
                                                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                                    title="Download attachment"
                                                                >
                                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    Download
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <!-- Empty State -->
                        <div x-show="!selectedEmail" class="h-full flex items-center justify-center bg-gray-50">
                            <div class="text-center max-w-md mx-auto px-6">
                                <svg class="mx-auto h-16 w-16 text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No email selected</h3>
                                <p class="text-gray-500 leading-relaxed">Choose an email from the list to view its contents, or compose a new email to get started.</p>
                                <div class="mt-6">
                                    <button @click="openCompose()" 
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Compose Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attachment Preview Modal -->
        <div x-show="showAttachmentPreview" class="fixed inset-0 z-50 flex items-center justify-center" style="display:none">
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showAttachmentPreview = false"></div>
            <div class="relative bg-white w-full max-w-4xl mx-4 rounded-lg shadow-lg max-h-[90vh] overflow-hidden">
                <div class="border-b px-4 py-3 flex items-center justify-between">
                    <h3 class="text-lg font-semibold" x-text="previewAttachment ? (previewAttachment.name || previewAttachment.filename || 'Attachment') : ''"></h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="showAttachmentPreview = false">✕</button>
                </div>
                <div class="p-4 max-h-[calc(90vh-120px)] overflow-auto">
                    <div x-show="previewAttachment && previewAttachment.type && previewAttachment.type.startsWith('image/')" class="text-center">
                        <img :src="`/attachments/${previewAttachment.id}/view`" :alt="previewAttachment.name || previewAttachment.filename" class="max-w-full h-auto mx-auto" style="max-height: 70vh;">
                    </div>
                    <div x-show="previewAttachment && previewAttachment.type && previewAttachment.type.includes('pdf')" class="w-full">
                        <iframe :src="`/attachments/${previewAttachment.id}/view#toolbar=0&navpanes=0&scrollbar=0`" class="w-full h-96 border-0" :title="previewAttachment.name || previewAttachment.filename"></iframe>
                    </div>
                </div>
                <div class="border-t px-4 py-3 flex items-center justify-end space-x-3">
                    <button @click="downloadAttachment(previewAttachment)" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Download
                    </button>
                    <button @click="showAttachmentPreview = false" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Compose Modal (moved inside x-data scope) -->
        <div x-show="showCompose" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
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
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm text-gray-700">Body</label>
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600">Signature:</label>
                                <select x-model="compose.signatureId" @change="applySignature()" class="text-sm rounded-md border-gray-300">
                                    <option value="">No signature</option>
                                    <template x-for="signature in signatures" :key="signature.id">
                                        <option :value="signature.id" x-text="signature.name"></option>
                                    </template>
                                </select>
                                <a href="/signatures" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">Manage</a>
                            </div>
                        </div>
                        <textarea rows="10" x-model="compose.body" class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Attachments</label>
                        <div class="mt-1">
                            <input type="file" x-ref="attachmentInput" @change="handleFileSelection" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="*/*">
                            <div class="mt-2 space-y-2">
                                <template x-for="(file, index) in compose.attachments" :key="index">
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded border">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm text-gray-700" x-text="file.name"></span>
                                            <span class="text-xs text-gray-500 ml-2" x-text="formatFileSize(file.size)"></span>
                                        </div>
                                        <button type="button" @click="removeAttachment(index)" class="text-red-500 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="border-t px-4 py-3 flex items-center justify-end gap-2">
                    <button type="button" class="inline-flex items-center rounded-md border border-gray-300 text-gray-700 bg-white px-3 py-2 text-sm font-semibold hover:bg-gray-50" @click="showCompose = false">Cancel</button>
                    <button type="button" class="inline-flex items-center rounded-md border border-emerald-600 text-white bg-emerald-600 px-3 py-2 text-sm font-semibold hover:bg-emerald-700" @click="sendEmail({ to: compose.to, cc: compose.cc, bcc: compose.bcc, subject: compose.subject, body: compose.body })">Send</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function emailApp() {
            return {
                accounts: @json($emailAccounts),
                selectedAccountId: null,
                signatures: [],
                folders: ['Inbox', 'Sent', 'Drafts', 'Trash', 'Spam'],
                selectedFolder: 'Inbox',
                searchQuery: '',
                startDate: '',
                endDate: '',
                syncDateRange: '',
                emails: [],
                selectedEmail: null,
                showAdvancedFilters: false,
                searchFields: {
                    from: true,
                    to: true,
                    subject: true,
                    body: true
                },
                filters: {
                    hasAttachments: false,
                    isUnread: false,
                    isFlagged: false
                },
                savedSearches: [],
                isLoading: false,
                selectedEmails: [],
                showSuggestions: false,
                searchSuggestions: [],
                recentSearches: [],
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
                    
                    this.isLoading = true;
                    const params = new URLSearchParams({ folder: this.selectedFolder, limit: 50 });
                    if (this.startDate) params.set('start_date', this.startDate);
                    if (this.endDate) params.set('end_date', this.endDate);
                    if (this.searchQuery) params.set('q', this.searchQuery);
                    
                    // Add search field filters
                    const searchFields = Object.keys(this.searchFields).filter(field => this.searchFields[field]);
                    if (searchFields.length > 0) {
                        params.set('search_fields', searchFields.join(','));
                    }
                    
                    // Add additional filters
                    if (this.filters.hasAttachments) params.set('has_attachments', '1');
                    if (this.filters.isUnread) params.set('is_unread', '1');
                    if (this.filters.isFlagged) params.set('is_flagged', '1');
                    
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
                        })
                        .finally(() => {
                            this.isLoading = false;
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
                    
                    // Show sync confirmation with date range
                    let confirmMessage = `Sync emails from ${this.selectedFolder} folder?`;
                    if (this.startDate && this.endDate) {
                        confirmMessage = `Sync emails from ${this.selectedFolder} folder between ${this.startDate} and ${this.endDate}?`;
                    } else if (this.startDate) {
                        confirmMessage = `Sync emails from ${this.selectedFolder} folder from ${this.startDate} onwards?`;
                    } else if (this.endDate) {
                        confirmMessage = `Sync emails from ${this.selectedFolder} folder until ${this.endDate}?`;
                    }
                    
                    if (!confirm(confirmMessage)) {
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
                    
                    // Show sync confirmation with date range
                    let confirmMessage = 'Sync emails from all folders?';
                    if (this.startDate && this.endDate) {
                        confirmMessage = `Sync emails from all folders between ${this.startDate} and ${this.endDate}?`;
                    } else if (this.startDate) {
                        confirmMessage = `Sync emails from all folders from ${this.startDate} onwards?`;
                    } else if (this.endDate) {
                        confirmMessage = `Sync emails from all folders until ${this.endDate}?`;
                    }
                    
                    if (!confirm(confirmMessage)) {
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
                    this.compose.attachments = [];
                    this.compose.signatureId = '';
                    this.showCompose = true;
                    // Load signatures after showing the modal
                    this.loadSignatures();
                },
                loadSignatures() {
                    if (!this.selectedAccountId) {
                        this.signatures = [];
                        return;
                    }
                    
                    fetch(`/signatures/account/${this.selectedAccountId}`)
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.signatures = res.signatures || [];
                                // Set default signature if available
                                const defaultSignature = res.signatures.find(s => s.is_default);
                                if (defaultSignature) {
                                    this.compose.signatureId = defaultSignature.id;
                                    this.applySignature();
                                }
                            } else {
                                this.signatures = [];
                            }
                        })
                        .catch(err => {
                            console.error('Failed to load signatures:', err);
                            this.signatures = [];
                        });
                },
                applySignature() {
                    if (!this.compose.signatureId) {
                        // Remove signature if none selected
                        this.compose.body = this.compose.body.replace(/\n\n--\s*\n.*$/s, '').replace(/\n\nKind regards.*$/s, '').replace(/\n\nBest regards.*$/s, '').replace(/\n\nSincerely.*$/s, '');
                        return;
                    }
                    
                    const signature = this.signatures.find(s => s.id == this.compose.signatureId);
                    if (!signature) return;
                    
                    // Remove any existing signature
                    this.compose.body = this.compose.body.replace(/\n\n--\s*\n.*$/s, '').replace(/\n\nKind regards.*$/s, '').replace(/\n\nBest regards.*$/s, '').replace(/\n\nSincerely.*$/s, '');
                    
                    // Add the new signature
                    const signatureContent = signature.content || '';
                    if (signatureContent) {
                        this.compose.body = this.compose.body + (this.compose.body ? '\n\n' : '') + signatureContent;
                    }
                },
                handleFileSelection(event) {
                    const files = Array.from(event.target.files);
                    this.compose.attachments = [...this.compose.attachments, ...files];
                },
                removeAttachment(index) {
                    this.compose.attachments.splice(index, 1);
                    // Reset the file input
                    this.$refs.attachmentInput.value = '';
                },
                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
                showAttachmentPreview: false,
                previewAttachment: null,
                compose: { to: '', cc: '', bcc: '', subject: '', body: '', attachments: [], signatureId: '' },
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
                    if (attachment.id) {
                        // Create a temporary link to download the attachment
                        const link = document.createElement('a');
                        link.href = `/attachments/${attachment.id}/download`;
                        link.download = attachment.filename || attachment.name || 'attachment';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        console.error('Attachment ID not found');
                        alert('Unable to download attachment: ID not found');
                    }
                },
                viewAttachment(attachment) {
                    if (attachment.id) {
                        // Set the preview attachment and show modal
                        this.previewAttachment = attachment;
                        this.showAttachmentPreview = true;
                    } else {
                        console.error('Attachment ID not found');
                        alert('Unable to view attachment: ID not found');
                    }
                },
                sendEmail({ to, cc, bcc, subject, body }) {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Create FormData for file uploads
                    const formData = new FormData();
                    formData.append('account_id', this.selectedAccountId);
                    formData.append('to', to);
                    formData.append('subject', subject);
                    formData.append('body', body);
                    formData.append('cc', cc || '');
                    formData.append('bcc', bcc || '');
                    
                    // Add attachments
                    this.compose.attachments.forEach((file, index) => {
                        formData.append(`attachments[${index}]`, file);
                    });
                    
                    fetch('/emails/send', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        credentials: 'same-origin',
                        mode: 'same-origin',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.ok) {
                            alert('Email sent successfully.');
                            this.showCompose = false;
                            this.compose = { to: '', cc: '', bcc: '', subject: '', body: '', attachments: [] };
                        } else {
                            alert('Failed to send email: ' + (res.error || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('An error occurred while sending email.');
                    });
                },
                
                // Advanced filtering methods
                toggleAdvancedFilters() {
                    this.showAdvancedFilters = !this.showAdvancedFilters;
                },
                
                setSyncDateRange() {
                    const today = new Date();
                    const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    
                    switch(this.syncDateRange) {
                        case 'today':
                            this.startDate = startOfDay.toISOString().split('T')[0];
                            this.endDate = today.toISOString().split('T')[0];
                            break;
                        case 'week':
                            const startOfWeek = new Date(startOfDay);
                            startOfWeek.setDate(startOfDay.getDate() - startOfDay.getDay());
                            this.startDate = startOfWeek.toISOString().split('T')[0];
                            this.endDate = today.toISOString().split('T')[0];
                            break;
                        case 'month':
                            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                            this.startDate = startOfMonth.toISOString().split('T')[0];
                            this.endDate = today.toISOString().split('T')[0];
                            break;
                        case 'custom':
                            // Show advanced filters for custom date selection
                            this.showAdvancedFilters = true;
                            break;
                        default:
                            // All time - clear dates
                            this.startDate = '';
                            this.endDate = '';
                            break;
                    }
                },
                
                clearDateRange() {
                    this.startDate = '';
                    this.endDate = '';
                    this.syncDateRange = '';
                },
                
                applyFilters() {
                    this.addToRecentSearches(this.searchQuery);
                    this.loadEmails();
                    this.showAdvancedFilters = false;
                },
                
                applyQuickFilter(period) {
                    const today = new Date();
                    const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    
                    switch(period) {
                        case 'today':
                            this.startDate = startOfDay.toISOString().split('T')[0];
                            this.endDate = today.toISOString().split('T')[0];
                            break;
                        case 'week':
                            const startOfWeek = new Date(startOfDay);
                            startOfWeek.setDate(startOfDay.getDate() - startOfDay.getDay());
                            this.startDate = startOfWeek.toISOString().split('T')[0];
                            this.endDate = today.toISOString().split('T')[0];
                            break;
                        case 'month':
                            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                            this.startDate = startOfMonth.toISOString().split('T')[0];
                            this.endDate = today.toISOString().split('T')[0];
                            break;
                    }
                    
                    this.loadEmails();
                },
                
                clearAllFilters() {
                    this.searchQuery = '';
                    this.startDate = '';
                    this.endDate = '';
                    this.searchFields = {
                        from: true,
                        to: true,
                        subject: true,
                        body: true
                    };
                    this.filters = {
                        hasAttachments: false,
                        isUnread: false,
                        isFlagged: false
                    };
                    this.loadEmails();
                },
                
                saveSearch() {
                    const searchName = prompt('Enter a name for this search:');
                    if (searchName) {
                        const search = {
                            id: Date.now(),
                            name: searchName,
                            query: this.searchQuery,
                            startDate: this.startDate,
                            endDate: this.endDate,
                            searchFields: { ...this.searchFields },
                            filters: { ...this.filters },
                            folder: this.selectedFolder
                        };
                        this.savedSearches.push(search);
                        this.saveSearchesToStorage();
                    }
                },
                
                loadSavedSearch(search) {
                    this.searchQuery = search.query || '';
                    this.startDate = search.startDate || '';
                    this.endDate = search.endDate || '';
                    this.searchFields = { ...search.searchFields };
                    this.filters = { ...search.filters };
                    this.selectedFolder = search.folder || 'Inbox';
                    this.loadEmails();
                },
                
                deleteSavedSearch(searchId) {
                    this.savedSearches = this.savedSearches.filter(s => s.id !== searchId);
                    this.saveSearchesToStorage();
                },
                
                saveSearchesToStorage() {
                    localStorage.setItem('emailSavedSearches', JSON.stringify(this.savedSearches));
                },
                
                loadSearchesFromStorage() {
                    const saved = localStorage.getItem('emailSavedSearches');
                    if (saved) {
                        this.savedSearches = JSON.parse(saved);
                    }
                    
                    const recent = localStorage.getItem('emailRecentSearches');
                    if (recent) {
                        this.recentSearches = JSON.parse(recent);
                    }
                },
                
                updateSearchSuggestions() {
                    if (!this.searchQuery || this.searchQuery.length < 2) {
                        this.searchSuggestions = [];
                        return;
                    }
                    
                    // Common search suggestions based on query
                    const commonSuggestions = [
                        'from:',
                        'to:',
                        'subject:',
                        'has:attachment',
                        'is:unread',
                        'is:read',
                        'is:flagged',
                        'before:',
                        'after:',
                        'size:large',
                        'size:small'
                    ];
                    
                    this.searchSuggestions = commonSuggestions
                        .filter(suggestion => suggestion.toLowerCase().includes(this.searchQuery.toLowerCase()))
                        .slice(0, 5);
                },
                
                applySuggestion(suggestion) {
                    this.searchQuery = suggestion;
                    this.showSuggestions = false;
                    this.addToRecentSearches(suggestion);
                    this.loadEmails();
                },
                
                addToRecentSearches(query) {
                    if (!query || query.trim() === '') return;
                    
                    // Remove if already exists
                    this.recentSearches = this.recentSearches.filter(s => s !== query);
                    
                    // Add to beginning
                    this.recentSearches.unshift(query);
                    
                    // Keep only last 10
                    this.recentSearches = this.recentSearches.slice(0, 10);
                    
                    // Save to localStorage
                    localStorage.setItem('emailRecentSearches', JSON.stringify(this.recentSearches));
                },
                
                // Bulk action methods
                selectAllEmails() {
                    this.selectedEmails = this.emails.map(email => email.id);
                },
                
                clearSelection() {
                    this.selectedEmails = [];
                },
                
                selectEmail(email) {
                    this.selectedEmail = email;
                    this.selectedEmails = [email.id];
                    this.showCompose = false;
                    this.showSettings = false;
                    
                    // Load EML content if available
                    this.loadEmailContent(email.id);
                },
                
                loadEmailContent(emailId) {
                    if (!emailId) return;
                    
                    // Show loading state
                    this.selectedEmail.isLoadingContent = true;
                    
                    fetch(`/emails/content/${emailId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.content) {
                                // Update email content with EML data
                                this.selectedEmail.text_body = data.content.text_body || this.selectedEmail.text_body;
                                this.selectedEmail.html_body = data.content.html_body || this.selectedEmail.html_body;
                                this.selectedEmail.body = data.content.body || this.selectedEmail.body;
                                this.selectedEmail.headers = data.content.headers || this.selectedEmail.headers;
                                this.selectedEmail.contentSource = data.source;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading email content:', error);
                        })
                        .finally(() => {
                            this.selectedEmail.isLoadingContent = false;
                        });
                },
                
                bulkMarkAsRead() {
                    if (this.selectedEmails.length === 0) return;
                    
                    fetch('/emails/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            action: 'mark_read',
                            email_ids: this.selectedEmails
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update local email data
                            this.emails.forEach(email => {
                                if (this.selectedEmails.includes(email.id)) {
                                    email.is_read = true;
                                }
                            });
                            this.selectedEmails = [];
                            alert('Emails marked as read successfully.');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating emails.');
                    });
                },
                
                bulkMarkAsUnread() {
                    if (this.selectedEmails.length === 0) return;
                    
                    fetch('/emails/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            action: 'mark_unread',
                            email_ids: this.selectedEmails
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update local email data
                            this.emails.forEach(email => {
                                if (this.selectedEmails.includes(email.id)) {
                                    email.is_read = false;
                                }
                            });
                            this.selectedEmails = [];
                            alert('Emails marked as unread successfully.');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating emails.');
                    });
                },
                
                bulkFlag() {
                    if (this.selectedEmails.length === 0) return;
                    
                    fetch('/emails/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            action: 'flag',
                            email_ids: this.selectedEmails
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update local email data
                            this.emails.forEach(email => {
                                if (this.selectedEmails.includes(email.id)) {
                                    email.is_flagged = true;
                                }
                            });
                            this.selectedEmails = [];
                            alert('Emails flagged successfully.');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating emails.');
                    });
                },
                
                bulkDelete() {
                    if (this.selectedEmails.length === 0) return;
                    
                    if (!confirm(`Are you sure you want to delete ${this.selectedEmails.length} email(s)?`)) {
                        return;
                    }
                    
                    fetch('/emails/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            email_ids: this.selectedEmails
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove deleted emails from local data
                            this.emails = this.emails.filter(email => !this.selectedEmails.includes(email.id));
                            this.selectedEmails = [];
                            alert('Emails deleted successfully.');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting emails.');
                    });
                },

                // Initialize the app
                init() {
                    this.loadSearchesFromStorage();
                }

            }
        }
    </script>
</x-app-layout>
