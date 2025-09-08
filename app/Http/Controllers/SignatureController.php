<?php

namespace App\Http\Controllers;

use App\Models\EmailSignature;
use App\Models\EmailAccount;
use App\Services\SignatureTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SignatureController extends Controller
{
    /**
     * Display a listing of signatures.
     */
    public function index(Request $request)
    {
        $accountId = $request->query('account_id');
        $userId = Auth::id();

        $query = EmailSignature::forUser($userId)->active();

        if ($accountId) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                  ->orWhereNull('account_id');
            });
        }

        $signatures = $query->orderBy('is_default', 'desc')
                          ->orderBy('name')
                          ->get();

        $accounts = EmailAccount::where('user_id', $userId)->get();

        return view('signatures.index', compact('signatures', 'accounts', 'accountId'));
    }

    /**
     * Show the form for creating a new signature.
     */
    public function create(Request $request)
    {
        $accountId = $request->query('account_id');
        $templateType = $request->query('template', 'custom');
        $userId = Auth::id();
        $accounts = EmailAccount::where('user_id', $userId)->get();
        $templates = SignatureTemplateService::getTemplates();
        $selectedTemplate = SignatureTemplateService::getTemplate($templateType);

        return view('signatures.create', compact('accounts', 'accountId', 'templates', 'selectedTemplate', 'templateType'));
    }

    /**
     * Store a newly created signature.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'html_content' => ['nullable', 'string'],
            'account_id' => ['nullable', 'exists:email_accounts,id'],
            'template_type' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'image', 'max:2048'],
            'is_default' => ['boolean'],
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_active'] = true;

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('signatures', 'public');
                $imagePaths[] = [
                    'path' => $path,
                    'alt' => $image->getClientOriginalName(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }
        $validated['images'] = $imagePaths;

        // If this is set as default, unset other defaults for the same account
        if ($validated['is_default'] ?? false) {
            EmailSignature::where('user_id', Auth::id())
                ->where('account_id', $validated['account_id'])
                ->update(['is_default' => false]);
        }

        $signature = EmailSignature::create($validated);

        return redirect()->route('signatures.index', ['account_id' => $validated['account_id']])
                        ->with('success', 'Signature created successfully.');
    }

    /**
     * Display the specified signature.
     */
    public function show(EmailSignature $signature)
    {
        $this->authorize('view', $signature);
        return view('signatures.show', compact('signature'));
    }

    /**
     * Show the form for editing the specified signature.
     */
    public function edit(EmailSignature $signature)
    {
        $this->authorize('update', $signature);
        $userId = Auth::id();
        $accounts = EmailAccount::where('user_id', $userId)->get();

        return view('signatures.edit', compact('signature', 'accounts'));
    }

    /**
     * Update the specified signature.
     */
    public function update(Request $request, EmailSignature $signature)
    {
        $this->authorize('update', $signature);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'html_content' => ['nullable', 'string'],
            'account_id' => ['nullable', 'exists:email_accounts,id'],
            'template_type' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'image', 'max:2048'],
            'existing_images' => ['nullable', 'array'],
            'is_default' => ['boolean'],
        ]);

        // Handle image uploads
        $imagePaths = $signature->images ?? [];
        
        // Add new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('signatures', 'public');
                $imagePaths[] = [
                    'path' => $path,
                    'alt' => $image->getClientOriginalName(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }

        // Keep existing images if specified
        if ($request->has('existing_images')) {
            $existingImages = collect($imagePaths)->filter(function ($image) use ($request) {
                return in_array($image['path'], $request->existing_images);
            })->values()->toArray();
            $imagePaths = $existingImages;
        }

        $validated['images'] = $imagePaths;

        // If this is set as default, unset other defaults for the same account
        if ($validated['is_default'] ?? false) {
            EmailSignature::where('user_id', Auth::id())
                ->where('account_id', $validated['account_id'])
                ->where('id', '!=', $signature->id)
                ->update(['is_default' => false]);
        }

        $signature->update($validated);

        return redirect()->route('signatures.index', ['account_id' => $validated['account_id']])
                        ->with('success', 'Signature updated successfully.');
    }

    /**
     * Remove the specified signature.
     */
    public function destroy(EmailSignature $signature)
    {
        $this->authorize('delete', $signature);
        $signature->delete();

        return redirect()->route('signatures.index')
                        ->with('success', 'Signature deleted successfully.');
    }

    /**
     * Toggle the active status of a signature.
     */
    public function toggle(EmailSignature $signature)
    {
        $this->authorize('update', $signature);
        $signature->update(['is_active' => !$signature->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $signature->is_active
        ]);
    }

    /**
     * Set a signature as default.
     */
    public function setDefault(EmailSignature $signature)
    {
        $this->authorize('update', $signature);
        $signature->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Signature set as default successfully.'
        ]);
    }

    /**
     * Get signatures for a specific account (AJAX endpoint).
     */
    public function getForAccount(Request $request)
    {
        $accountId = $request->query('account_id');
        $userId = Auth::id();

        $signatures = EmailSignature::forUser($userId)
            ->where(function ($query) use ($accountId) {
                $query->where('account_id', $accountId)
                      ->orWhereNull('account_id');
            })
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'signatures' => $signatures->map(function ($signature) {
                return [
                    'id' => $signature->id,
                    'name' => $signature->name,
                    'content' => $signature->content,
                    'html_content' => $signature->html_content,
                    'is_default' => $signature->is_default,
                ];
            })
        ]);
    }

    /**
     * Upload an image for a signature.
     */
    public function uploadImage(Request $request, EmailSignature $signature)
    {
        $this->authorize('update', $signature);

        $request->validate([
            'image' => ['required', 'file', 'image', 'max:2048'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $image = $request->file('image');
        $path = $image->store('signatures', 'public');
        
        $signature->addImage($path, $request->input('alt', $image->getClientOriginalName()));

        return response()->json([
            'success' => true,
            'image' => [
                'path' => $path,
                'url' => $signature->getImageUrl($path),
                'alt' => $request->input('alt', $image->getClientOriginalName()),
            ]
        ]);
    }

    /**
     * Remove an image from a signature.
     */
    public function removeImage(Request $request, EmailSignature $signature)
    {
        $this->authorize('update', $signature);

        $request->validate([
            'image_path' => ['required', 'string'],
        ]);

        $imagePath = $request->input('image_path');
        
        // Delete the file from storage
        Storage::disk('public')->delete($imagePath);
        
        // Remove from signature
        $signature->removeImage($imagePath);

        return response()->json([
            'success' => true,
            'message' => 'Image removed successfully.'
        ]);
    }

    /**
     * Preview a signature.
     */
    public function preview(EmailSignature $signature)
    {
        $this->authorize('view', $signature);
        
        return view('signatures.preview', compact('signature'));
    }

    /**
     * Get template preview.
     */
    public function getTemplatePreview(Request $request)
    {
        $templateType = $request->query('template', 'custom');
        $template = SignatureTemplateService::getTemplate($templateType);
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        return response()->json([
            'success' => true,
            'template' => $template
        ]);
    }
}