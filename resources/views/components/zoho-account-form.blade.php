<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-show="showZohoForm" x-cloak>
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add Zoho Account</h3>
                <button @click="showZohoForm = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="addZohoAccount" class="space-y-4">
                <div>
                    <label for="zoho_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input 
                        type="email" 
                        id="zoho_email" 
                        x-model="zohoForm.email"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="your-email@zoho.com"
                    >
                </div>
                
                <div>
                    <label for="zoho_password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input 
                        type="password" 
                        id="zoho_password" 
                        x-model="zohoForm.password"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Your Zoho password"
                    >
                </div>
                
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="zoho_remember" 
                        x-model="zohoForm.remember"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    >
                    <label for="zoho_remember" class="ml-2 block text-sm text-gray-900">
                        Remember credentials
                    </label>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button 
                        type="button" 
                        @click="showZohoForm = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        :disabled="isAddingAccount"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        <span x-show="!isAddingAccount">Add Account</span>
                        <span x-show="isAddingAccount">Adding...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
