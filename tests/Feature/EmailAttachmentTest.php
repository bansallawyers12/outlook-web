<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmailAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_send_email_with_attachments()
    {
        // Create a user and email account
        $user = User::factory()->create();
        $account = EmailAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'brevo',
            'email' => 'test@example.com',
            'access_token' => 'test_token'
        ]);

        // Create a test file
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.txt', 100);

        // Send email with attachment
        $response = $this->actingAs($user)->post('/emails/send', [
            'account_id' => $account->id,
            'to' => 'recipient@example.com',
            'subject' => 'Test Email with Attachment',
            'body' => 'This is a test email with an attachment.',
            'cc' => '',
            'bcc' => '',
            'attachments' => [$file]
        ]);

        // The response should be successful (even if email sending fails due to test environment)
        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_can_send_email_without_attachments()
    {
        // Create a user and email account
        $user = User::factory()->create();
        $account = EmailAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'brevo',
            'email' => 'test@example.com',
            'access_token' => 'test_token'
        ]);

        // Send email without attachment
        $response = $this->actingAs($user)->post('/emails/send', [
            'account_id' => $account->id,
            'to' => 'recipient@example.com',
            'subject' => 'Test Email without Attachment',
            'body' => 'This is a test email without an attachment.',
            'cc' => '',
            'bcc' => ''
        ]);

        // The response should be successful
        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_attachment_validation()
    {
        // Create a user and email account
        $user = User::factory()->create();
        $account = EmailAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'brevo',
            'email' => 'test@example.com',
            'access_token' => 'test_token'
        ]);

        // Try to send email with invalid attachment (too large)
        $file = UploadedFile::fake()->create('large_file.txt', 15000); // 15MB, exceeds 10MB limit

        $response = $this->actingAs($user)->post('/emails/send', [
            'account_id' => $account->id,
            'to' => 'recipient@example.com',
            'subject' => 'Test Email with Large Attachment',
            'body' => 'This should fail due to large attachment.',
            'attachments' => [$file]
        ]);

        // Should fail validation
        $response->assertStatus(422);
    }
}
