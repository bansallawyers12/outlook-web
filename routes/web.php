<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\EmailAccountController;
use App\Http\Controllers\SignatureController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $emailAccounts = \App\Models\EmailAccount::where('user_id', auth()->id())
        ->get()
        ->map(function ($account) {
            return [
                'id' => $account->id,
                'label' => ucfirst($account->provider) . ' - ' . $account->email
            ];
        });
    
    return view('dashboard', compact('emailAccounts'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Email account management routes
    Route::resource('accounts', EmailAccountController::class);
    Route::post('/accounts/{account}/test-connection', [EmailAccountController::class, 'testConnection'])->name('accounts.test-connection');
    Route::post('/accounts/{account}/test-authentication', [EmailAccountController::class, 'testAuthentication'])->name('accounts.test-authentication');

    Route::post('/emails/send', [EmailController::class, 'send'])->name('emails.send');
    Route::post('/emails/save-draft', [EmailController::class, 'saveDraft'])->name('emails.save-draft');
    Route::get('/emails/drafts', [EmailController::class, 'drafts'])->name('emails.drafts');
    Route::get('/emails/draft/{id}', [EmailController::class, 'getDraft'])->name('emails.draft.get');
    Route::delete('/emails/draft/{id}', [EmailController::class, 'deleteDraft'])->name('emails.draft.delete');
    Route::get('/emails/reply/{id}', [EmailController::class, 'getReplyData'])->name('emails.reply.get');
    Route::get('/emails/content/{id}', [EmailController::class, 'getEmailContent'])->name('emails.content.get');
    Route::get('/emails/compose', [EmailController::class, 'compose'])->name('emails.compose');
    Route::get('/emails/sync/{accountId}', [EmailController::class, 'sync'])->name('emails.sync.get');
    Route::post('/emails/sync/{accountId}', [EmailController::class, 'sync'])->name('emails.sync.post');
    Route::post('/emails/bulk-action', [EmailController::class, 'bulkAction'])->name('emails.bulk-action');
    Route::post('/auth/zoho/add', [AuthController::class, 'addZohoAccount'])->name('auth.zoho.add');

    // Attachment download and view
    Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/attachments/{id}/view', [AttachmentController::class, 'view'])->name('attachments.view');

    // Labels minimal API for UI
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::post('/labels/apply', [LabelController::class, 'apply'])->name('labels.apply');
    Route::post('/labels/remove', [LabelController::class, 'remove'])->name('labels.remove');

    // Email signatures management
    Route::resource('signatures', SignatureController::class);
    Route::post('/signatures/{signature}/toggle', [SignatureController::class, 'toggle'])->name('signatures.toggle');
    Route::post('/signatures/{signature}/set-default', [SignatureController::class, 'setDefault'])->name('signatures.set-default');
    Route::get('/signatures/account/{account_id}', [SignatureController::class, 'getForAccount'])->name('signatures.get-for-account');
    Route::post('/signatures/{signature}/upload-image', [SignatureController::class, 'uploadImage'])->name('signatures.upload-image');
    Route::delete('/signatures/{signature}/remove-image', [SignatureController::class, 'removeImage'])->name('signatures.remove-image');
    Route::get('/signatures/{signature}/preview', [SignatureController::class, 'preview'])->name('signatures.preview');
    Route::get('/signatures/template/preview', [SignatureController::class, 'getTemplatePreview'])->name('signatures.template-preview');
});

require __DIR__.'/auth.php';

// OAuth routes
Route::get('/auth/{provider}', [AuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('oauth.callback');

