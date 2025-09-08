<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Email Signatures') }}
            </h2>
            <div class="flex space-x-2">
                <select id="accountFilter" class="rounded-md border-gray-300 text-sm">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ $accountId == $account->id ? 'selected' : '' }}>
                            {{ ucfirst($account->provider) }} - {{ $account->email }}
                        </option>
                    @endforeach
                </select>
                <a href="{{ route('signatures.create', ['account_id' => $accountId]) }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                    New Signature
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($signatures->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid gap-4">
                            @foreach($signatures as $signature)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    {{ $signature->name }}
                                                </h3>
                                                @if($signature->is_default)
                                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                                        Default
                                                    </span>
                                                @endif
                                                @if(!$signature->is_active)
                                                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            @if($signature->emailAccount)
                                                <p class="text-sm text-gray-600 mb-2">
                                                    Account: {{ ucfirst($signature->emailAccount->provider) }} - {{ $signature->emailAccount->email }}
                                                </p>
                                            @else
                                                <p class="text-sm text-gray-600 mb-2">
                                                    Global signature (all accounts)
                                                </p>
                                            @endif

                                            <div class="prose max-w-none text-sm text-gray-700 mb-3">
                                                {!! nl2br(e($signature->content)) !!}
                                            </div>

                                            @if($signature->images && count($signature->images) > 0)
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                @foreach($signature->images as $image)
                                                <img src="{{ $signature->getImageUrl($image['path']) }}" 
                                                     alt="{{ $image['alt'] ?? '' }}" 
                                                     class="w-12 h-12 object-cover rounded border">
                                                @endforeach
                                            </div>
                                            @endif

                                            @if($signature->template_type && $signature->template_type !== 'custom')
                                            <div class="text-xs text-gray-500">
                                                Template: <span class="capitalize">{{ $signature->template_type }}</span>
                                            </div>
                                            @endif
                                        </div>

                                        <div class="flex space-x-2 ml-4">
                                            <a href="{{ route('signatures.preview', $signature) }}" 
                                               class="text-green-600 hover:text-green-800 text-sm">
                                                Preview
                                            </a>
                                            <a href="{{ route('signatures.edit', $signature) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-sm">
                                                Edit
                                            </a>
                                            
                                            @if(!$signature->is_default)
                                                <form method="POST" action="{{ route('signatures.set-default', $signature) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="text-green-600 hover:text-green-800 text-sm">
                                                        Set Default
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('signatures.toggle', $signature) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="text-yellow-600 hover:text-yellow-800 text-sm">
                                                    {{ $signature->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('signatures.destroy', $signature) }}" 
                                                  class="inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this signature?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No signatures found</h3>
                        <p class="text-gray-500 mb-4">
                            @if($accountId)
                                No signatures found for this account. Create your first signature to get started.
                            @else
                                No signatures found. Create your first signature to get started.
                            @endif
                        </p>
                        <a href="{{ route('signatures.create', ['account_id' => $accountId]) }}" 
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Create Signature
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('accountFilter').addEventListener('change', function() {
            const accountId = this.value;
            const url = new URL(window.location);
            if (accountId) {
                url.searchParams.set('account_id', accountId);
            } else {
                url.searchParams.delete('account_id');
            }
            window.location.href = url.toString();
        });
    </script>
</x-app-layout>
