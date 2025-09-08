<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EmailSignature;

class EmailSignatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        
        if (!$user) {
            $this->command->info('No users found. Please create a user first.');
            return;
        }

        // Create sample signatures
        $signatures = [
            [
                'name' => 'Professional Signature',
                'content' => "Kind Regards,\nJohn Doe\nSoftware Developer\nPhone: (555) 123-4567\nEmail: john.doe@example.com",
                'html_content' => '<p>Kind Regards,<br><strong>John Doe</strong><br>Software Developer<br>Phone: (555) 123-4567<br>Email: john.doe@example.com</p>',
                'template_type' => 'professional',
                'is_default' => true,
                'is_active' => true
            ],
            [
                'name' => 'Business Signature',
                'content' => "Thanks and Regards,\n\nARUN BANSAL\nMaster of Laws (IN) | Grad Diploma of Migration Law (AU)\nRegistered Migration Agent\nMARN: 2418466\n\nT: 03 9602 1330\nM: 0404 000 058\nW: www.bansalimmigration.com.au\nE: arun@bansalimmigration.com.au\nA: Level 8/278 Collins St, Melbourne 3000",
                'html_content' => '
                <div style="font-family: Arial, sans-serif; font-size: 12px; color: #333; max-width: 600px;">
                    <p style="margin: 0 0 10px 0;">Thanks and Regards,</p>
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <div style="width: 60px; height: 60px; background: #1e40af; margin-right: 15px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">B</div>
                        <div>
                            <h3 style="font-size: 16px; font-weight: bold; margin: 0 0 5px 0; color: #1e40af;">ARUN BANSAL</h3>
                            <p style="font-size: 11px; margin: 0; color: #666;">Master of Laws (IN) | Grad Diploma of Migration Law (AU)</p>
                            <p style="font-size: 11px; margin: 0; color: #666; font-weight: bold;">Registered Migration Agent</p>
                            <p style="font-size: 11px; margin: 0; color: #666;">MARN: 2418466</p>
                        </div>
                    </div>
                    <div style="margin: 10px 0;">
                        <p style="margin: 2px 0; font-size: 11px;"><strong>T:</strong> 03 9602 1330</p>
                        <p style="margin: 2px 0; font-size: 11px;"><strong>M:</strong> 0404 000 058</p>
                        <p style="margin: 2px 0; font-size: 11px;"><strong>W:</strong> <a href="http://www.bansalimmigration.com.au" style="color: #1e40af;">www.bansalimmigration.com.au</a></p>
                        <p style="margin: 2px 0; font-size: 11px;"><strong>E:</strong> <a href="mailto:arun@bansalimmigration.com.au" style="color: #1e40af;">arun@bansalimmigration.com.au</a></p>
                        <p style="margin: 2px 0; font-size: 11px;"><strong>A:</strong> Level 8/278 Collins St, Melbourne 3000</p>
                    </div>
                    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                        <p style="font-style: italic; font-size: 11px; color: #6b7280; margin: 0;">We are here to serve you</p>
                    </div>
                </div>',
                'template_type' => 'business',
                'is_default' => false,
                'is_active' => true
            ],
            [
                'name' => 'Simple Signature',
                'content' => "Best regards,\nJohn",
                'html_content' => '<p>Best regards,<br>John</p>',
                'template_type' => 'custom',
                'is_default' => false,
                'is_active' => true
            ]
        ];

        foreach ($signatures as $signatureData) {
            EmailSignature::create([
                'user_id' => $user->id,
                'name' => $signatureData['name'],
                'content' => $signatureData['content'],
                'html_content' => $signatureData['html_content'],
                'template_type' => $signatureData['template_type'],
                'is_default' => $signatureData['is_default'],
                'is_active' => $signatureData['is_active']
            ]);
        }

        $this->command->info('Email signatures created successfully!');
    }
}