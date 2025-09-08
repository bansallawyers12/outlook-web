<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Signature Preview') }} - {{ $signature->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('signatures.edit', $signature) }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Edit Signature
                </a>
                <a href="{{ route('signatures.index', ['account_id' => $signature->account_id]) }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Back to Signatures
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Plain Text Version</h3>
                        <div class="bg-gray-50 p-4 rounded-md border">
                            <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ $signature->content }}</pre>
                        </div>
                    </div>

                    @if($signature->html_content)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">HTML Version</h3>
                        <div class="border border-gray-200 rounded-md p-4 bg-white">
                            <div class="prose max-w-none">
                                {!! $signature->html_content !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($signature->images && count($signature->images) > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Images</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($signature->images as $image)
                            <div class="border border-gray-200 rounded-md p-2">
                                <img src="{{ $signature->getImageUrl($image['path']) }}" 
                                     alt="{{ $image['alt'] ?? '' }}" 
                                     class="w-full h-32 object-cover rounded">
                                <p class="text-xs text-gray-500 mt-1 text-center">{{ $image['alt'] ?? 'No alt text' }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <h4 class="text-sm font-medium text-blue-900 mb-2">Signature Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-blue-800">Name:</span>
                                <span class="text-blue-700">{{ $signature->name }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-blue-800">Template Type:</span>
                                <span class="text-blue-700 capitalize">{{ $signature->template_type }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-blue-800">Account:</span>
                                <span class="text-blue-700">
                                    @if($signature->emailAccount)
                                        {{ ucfirst($signature->emailAccount->provider) }} - {{ $signature->emailAccount->email }}
                                    @else
                                        Global (All Accounts)
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="font-medium text-blue-800">Status:</span>
                                <span class="text-blue-700">
                                    @if($signature->is_default)
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Default</span>
                                    @elseif($signature->is_active)
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Active</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Inactive</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
