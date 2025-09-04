@props(['attachment'])

<div class="attachment-item bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
        <div class="flex items-start space-x-3 flex-1">
            <!-- File type icon -->
            <div class="flex-shrink-0">
                @if($attachment->isImage())
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @elseif($attachment->isPdf())
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @elseif($attachment->isDocument())
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @else
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @endif
            </div>

            <!-- File info -->
            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ $attachment->display_name ?: $attachment->filename }}
                </h4>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $attachment->formatted_file_size }}
                    @if($attachment->content_type)
                        • {{ $attachment->content_type }}
                    @endif
                </p>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="flex items-center space-x-2 ml-4">
            @if($attachment->canPreview())
                <button 
                    onclick="openAttachmentPreview({{ $attachment->id }})"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    title="Preview {{ $attachment->filename }}"
                >
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                    </svg>
                    View
                </button>
            @endif
            
            <a 
                href="{{ route('attachments.download', $attachment->id) }}"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                title="Download {{ $attachment->filename }}"
            >
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                Download
            </a>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="attachment-preview-modal-{{ $attachment->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Modal header -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ $attachment->display_name ?: $attachment->filename }}
                </h3>
                <button 
                    onclick="closeAttachmentPreview({{ $attachment->id }})"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal content -->
            <div class="max-h-96 overflow-auto">
                @if($attachment->isImage())
                    <img 
                        src="{{ route('attachments.view', $attachment->id) }}" 
                        alt="{{ $attachment->filename }}"
                        class="max-w-full h-auto mx-auto"
                        style="max-height: 80vh;"
                    />
                @elseif($attachment->isPdf())
                    <iframe 
                        src="{{ route('attachments.view', $attachment->id) }}#toolbar=0&navpanes=0&scrollbar=0"
                        class="w-full h-96 border-0"
                        title="{{ $attachment->filename }}"
                    ></iframe>
                @endif
            </div>
            
            <!-- Modal footer -->
            <div class="flex items-center justify-end space-x-3 mt-4 pt-4 border-t">
                <a 
                    href="{{ route('attachments.download', $attachment->id) }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Download
                </a>
                <button 
                    onclick="closeAttachmentPreview({{ $attachment->id }})"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openAttachmentPreview(attachmentId) {
    document.getElementById('attachment-preview-modal-' + attachmentId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeAttachmentPreview(attachmentId) {
    document.getElementById('attachment-preview-modal-' + attachmentId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('bg-opacity-50')) {
        const modal = event.target.closest('[id^="attachment-preview-modal-"]');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModal = document.querySelector('[id^="attachment-preview-modal-"]:not(.hidden)');
        if (openModal) {
            openModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }
});
</script>

