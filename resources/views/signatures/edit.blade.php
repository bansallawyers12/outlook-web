<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Email Signature') }}
            </h2>
            <a href="{{ route('signatures.index', ['account_id' => $signature->account_id]) }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                Back to Signatures
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('signatures.update', $signature) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Signature Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $signature->name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                                       required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="account_id" class="block text-sm font-medium text-gray-700">
                                    Email Account
                                </label>
                                <select id="account_id" 
                                        name="account_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('account_id') border-red-500 @enderror">
                                    <option value="">Global (All Accounts)</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old('account_id', $signature->account_id) == $account->id ? 'selected' : '' }}>
                                            {{ ucfirst($account->provider) }} - {{ $account->email }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('account_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">
                                    Leave empty to create a global signature for all accounts.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700">
                                Signature Content (Plain Text) <span class="text-red-500">*</span>
                            </label>
                            <textarea id="content" 
                                      name="content" 
                                      rows="8"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('content') border-red-500 @enderror"
                                      placeholder="Enter your signature content here..."
                                      required>{{ old('content', $signature->content) }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                This will be used for plain text emails.
                            </p>
                        </div>

                        <div>
                            <label for="template_type" class="block text-sm font-medium text-gray-700">
                                Template Type
                            </label>
                            <select id="template_type" 
                                    name="template_type" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('template_type') border-red-500 @enderror">
                                <option value="custom" {{ old('template_type', $signature->template_type) == 'custom' ? 'selected' : '' }}>Custom</option>
                                <option value="professional" {{ old('template_type', $signature->template_type) == 'professional' ? 'selected' : '' }}>Professional</option>
                                <option value="business" {{ old('template_type', $signature->template_type) == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="creative" {{ old('template_type', $signature->template_type) == 'creative' ? 'selected' : '' }}>Creative</option>
                            </select>
                            @error('template_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($signature->images && count($signature->images) > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Current Images
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                @foreach($signature->images as $image)
                                <div class="relative group">
                                    <img src="{{ $signature->getImageUrl($image['path']) }}" 
                                         alt="{{ $image['alt'] ?? '' }}" 
                                         class="w-full h-24 object-cover rounded border">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded flex items-center justify-center">
                                        <button type="button" 
                                                onclick="removeImage('{{ $image['path'] }}')"
                                                class="text-white bg-red-500 hover:bg-red-600 px-2 py-1 rounded text-sm">
                                            Remove
                                        </button>
                                    </div>
                                    <input type="hidden" name="existing_images[]" value="{{ $image['path'] }}" id="existing_{{ $loop->index }}">
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div>
                            <label for="images" class="block text-sm font-medium text-gray-700">
                                Add New Images
                            </label>
                            <input type="file" 
                                   id="images" 
                                   name="images[]" 
                                   multiple
                                   accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('images') border-red-500 @enderror">
                            @error('images')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Upload additional images for your signature (max 2MB each). Supported formats: JPG, PNG, GIF, WebP.
                            </p>
                        </div>

                        <div>
                            <label for="html_content" class="block text-sm font-medium text-gray-700 mb-2">
                                Signature Content (HTML)
                            </label>
                            <div class="mt-1">
                                <div id="html-editor" class="border border-gray-300 rounded-lg min-h-[400px] focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 shadow-sm">
                                    <!-- Enhanced Toolbar -->
                                    <div class="toolbar border-b border-gray-200 p-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-t-lg">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <!-- Font Family -->
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs text-gray-600 font-medium">Font:</label>
                                                <select id="fontFamily" onchange="formatText('fontName', this.value)" class="px-2 py-1 text-sm border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                    <option value="Arial">Arial</option>
                                                    <option value="Aptos Display">Aptos Display</option>
                                                    <option value="Calibri">Calibri</option>
                                                    <option value="Times New Roman">Times New Roman</option>
                                                    <option value="Helvetica">Helvetica</option>
                                                    <option value="Georgia">Georgia</option>
                                                    <option value="Verdana">Verdana</option>
                                                </select>
                                            </div>

                                            <!-- Font Size -->
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs text-gray-600 font-medium">Size:</label>
                                                <select id="fontSize" onchange="formatText('fontSize', this.value)" class="px-2 py-1 text-sm border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                    <option value="8px">8</option>
                                                    <option value="9px">9</option>
                                                    <option value="10px">10</option>
                                                    <option value="11px">11</option>
                                                    <option value="12px">12</option>
                                                    <option value="13px">13</option>
                                                    <option value="14px">14</option>
                                                    <option value="15px" selected>15</option>
                                                    <option value="16px">16</option>
                                                    <option value="18px">18</option>
                                                    <option value="20px">20</option>
                                                    <option value="24px">24</option>
                                                    <option value="28px">28</option>
                                                    <option value="32px">32</option>
                                                </select>
                                            </div>

                                            <div class="border-l border-gray-300 h-6"></div>

                                            <!-- Formatting Buttons -->
                                            <div class="flex items-center gap-1">
                                                <button type="button" onclick="formatText('bold')" class="w-8 h-8 flex items-center justify-center text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Bold">
                                                    <strong>B</strong>
                                                </button>
                                                <button type="button" onclick="formatText('italic')" class="w-8 h-8 flex items-center justify-center text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Italic">
                                                    <em>I</em>
                                                </button>
                                                <button type="button" onclick="formatText('underline')" class="w-8 h-8 flex items-center justify-center text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Underline">
                                                    <u>U</u>
                                                </button>
                                            </div>

                                            <div class="border-l border-gray-300 h-6"></div>

                                            <!-- Color Picker -->
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs text-gray-600 font-medium">Color:</label>
                                                <input type="color" id="textColor" onchange="formatText('foreColor', this.value)" value="#1e40af" class="w-8 h-8 border border-gray-300 rounded cursor-pointer">
                                            </div>

                                            <div class="border-l border-gray-300 h-6"></div>

                                            <!-- Alignment -->
                                            <div class="flex items-center gap-1">
                                                <button type="button" onclick="formatText('justifyLeft')" class="w-8 h-8 flex items-center justify-center text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Align Left">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                                    </svg>
                                                </button>
                                                <button type="button" onclick="formatText('justifyCenter')" class="w-8 h-8 flex items-center justify-center text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Align Center">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm2 4a1 1 0 011-1h6a1 1 0 110 2H6a1 1 0 01-1-1zm-2 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm2 4a1 1 0 011-1h6a1 1 0 110 2H6a1 1 0 01-1-1z"/>
                                                    </svg>
                                                </button>
                                                <button type="button" onclick="formatText('justifyRight')" class="w-8 h-8 flex items-center justify-center text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Align Right">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm6 4a1 1 0 011-1h6a1 1 0 110 2H10a1 1 0 01-1-1zm-6 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm6 4a1 1 0 011-1h6a1 1 0 110 2H10a1 1 0 01-1-1z"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="border-l border-gray-300 h-6"></div>

                                            <!-- Insert Options -->
                                            <div class="flex items-center gap-1">
                                                <button type="button" onclick="insertImage()" class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Insert Image">
                                                    📷 Image
                                                </button>
                                                <button type="button" onclick="insertLink()" class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Insert Link">
                                                    🔗 Link
                                                </button>
                                                <button type="button" onclick="insertBusinessCard()" class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Business Card">
                                                    💼 Business Card
                                                </button>
                                            </div>

                                            <div class="border-l border-gray-300 h-6"></div>

                                            <!-- Preview Options -->
                                            <div class="flex items-center gap-1">
                                                <button type="button" onclick="previewSignature()" class="px-3 py-1 text-sm bg-blue-600 text-white border border-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Preview">
                                                    👁️ Preview
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Editor Content Area -->
                                    <div id="html-editor-content" 
                                         contenteditable="true" 
                                         class="p-6 min-h-[350px] focus:outline-none bg-white"
                                         data-placeholder="Enter your HTML signature content here..."
                                         style="font-family: 'Aptos Display', Arial, sans-serif; font-size: 15px; line-height: 1.4;">{{ old('html_content', $signature->html_content) }}</div>
                                </div>
                                <textarea id="html_content" 
                                          name="html_content" 
                                          class="hidden">{{ old('html_content', $signature->html_content) }}</textarea>
                            </div>
                            @error('html_content')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Optional. This will be used for HTML emails. If not provided, the plain text version will be used.
                            </p>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="is_default" 
                                   name="is_default" 
                                   value="1"
                                   {{ old('is_default', $signature->is_default) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="is_default" class="ml-2 block text-sm text-gray-900">
                                Set as default signature for this account
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('signatures.index', ['account_id' => $signature->account_id]) }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Signature
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced rich text editor functionality
        function formatText(command, value = null) {
            document.execCommand(command, false, value);
            updateHtmlContent();
            updateToolbarState();
        }

        function insertImage() {
            const url = prompt('Enter image URL:');
            if (url) {
                const img = document.createElement('img');
                img.src = url;
                img.style.maxWidth = '200px';
                img.style.height = 'auto';
                img.style.margin = '5px';
                img.style.borderRadius = '4px';
                document.execCommand('insertHTML', false, img.outerHTML);
                updateHtmlContent();
            }
        }

        function insertLink() {
            const url = prompt('Enter link URL:');
            const text = prompt('Enter link text:');
            if (url && text) {
                const link = `<a href="${url}" target="_blank" style="color: #1e40af; text-decoration: none;">${text}</a>`;
                document.execCommand('insertHTML', false, link);
                updateHtmlContent();
            }
        }

        function insertBusinessCard() {
            const businessCard = `
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin: 10px 0; background: #f9fafb; max-width: 300px;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <div style="width: 40px; height: 40px; background: #1e40af; border-radius: 50%; margin-right: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">B</div>
                        <div>
                            <div style="font-weight: bold; color: #1e40af; font-size: 14px;">[Your Name]</div>
                            <div style="font-size: 12px; color: #6b7280;">[Your Title]</div>
                        </div>
                    </div>
                    <div style="font-size: 11px; color: #374151;">
                        <div>📞 [Phone]</div>
                        <div>✉️ [Email]</div>
                        <div>🌐 [Website]</div>
                    </div>
                </div>
            `;
            document.execCommand('insertHTML', false, businessCard);
            updateHtmlContent();
        }

        function previewSignature() {
            const content = document.getElementById('html-editor-content').innerHTML;
            const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Signature Preview</title>
                    <style>
                        body { font-family: 'Aptos Display', Arial, sans-serif; padding: 20px; background: #f9fafb; }
                        .signature-preview { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; }
                    </style>
                </head>
                <body>
                    <div class="signature-preview">
                        ${content}
                    </div>
                </body>
                </html>
            `);
        }

        function updateHtmlContent() {
            const content = document.getElementById('html-editor-content').innerHTML;
            document.getElementById('html_content').value = content;
        }

        function updateToolbarState() {
            // Update font family dropdown
            const fontFamily = document.queryCommandValue('fontName');
            if (fontFamily) {
                document.getElementById('fontFamily').value = fontFamily;
            }

            // Update font size dropdown
            const fontSize = document.queryCommandValue('fontSize');
            if (fontSize) {
                document.getElementById('fontSize').value = fontSize;
            }

            // Update color picker
            const textColor = document.queryCommandValue('foreColor');
            if (textColor && textColor !== 'rgb(0, 0, 0)') {
                document.getElementById('textColor').value = textColor;
            }
        }

        function removeImage(imagePath) {
            if (confirm('Are you sure you want to remove this image?')) {
                // Remove from DOM
                const imageElement = document.querySelector(`img[src*="${imagePath}"]`);
                if (imageElement) {
                    imageElement.closest('.relative.group').remove();
                }
                
                // Remove from hidden input
                const hiddenInput = document.querySelector(`input[value="${imagePath}"]`);
                if (hiddenInput) {
                    hiddenInput.remove();
                }
            }
        }

        // Initialize editor
        document.addEventListener('DOMContentLoaded', function() {
            const editor = document.getElementById('html-editor-content');
            const textarea = document.getElementById('html_content');
            
            // Update textarea when editor content changes
            editor.addEventListener('input', updateHtmlContent);
            editor.addEventListener('paste', function(e) {
                setTimeout(updateHtmlContent, 100);
            });

            // Handle selection changes to update toolbar
            editor.addEventListener('selectionchange', updateToolbarState);

            // Handle placeholder
            if (editor.textContent.trim() === '') {
                editor.textContent = editor.dataset.placeholder;
                editor.classList.add('text-gray-400');
            }

            editor.addEventListener('focus', function() {
                if (this.textContent === this.dataset.placeholder) {
                    this.textContent = '';
                    this.classList.remove('text-gray-400');
                }
            });

            editor.addEventListener('blur', function() {
                if (this.textContent.trim() === '') {
                    this.textContent = this.dataset.placeholder;
                    this.classList.add('text-gray-400');
                }
            });

            // Form submission
            document.querySelector('form').addEventListener('submit', function() {
                updateHtmlContent();
            });

            // Initialize toolbar state
            updateToolbarState();
        });
    </script>
</x-app-layout>
