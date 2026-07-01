<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'key' => 'password-reset',
                'name' => 'Password Reset',
                'description' => 'Sent when a user requests a password reset.',
                'subject' => 'Reset your password on {{site_name}}',
                'body' => '<p>Hello {{user_name}},</p><p>You recently requested to reset your password. Click the button below to set a new one:</p><p><a href="{{reset_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Reset Password</a></p><p>This link will expire in 60 minutes. If you did not request a password reset, no further action is required.</p><p>— {{site_name}}</p>',
                'merge_tags' => json_encode([
                    ['tag' => '{{user_name}}', 'description' => "The user's name"],
                    ['tag' => '{{reset_url}}', 'description' => 'Password reset link'],
                    ['tag' => '{{site_name}}', 'description' => 'Your site name'],
                ]),
            ],
            [
                'key' => 'email-verification',
                'name' => 'Email Verification',
                'description' => 'Sent when a user needs to verify their email address.',
                'subject' => 'Verify your email address on {{site_name}}',
                'body' => '<p>Hello {{user_name}},</p><p>Please verify your email address by clicking the button below:</p><p><a href="{{verification_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Verify Email</a></p><p>If you did not create an account, no further action is required.</p><p>— {{site_name}}</p>',
                'merge_tags' => json_encode([
                    ['tag' => '{{user_name}}', 'description' => "The user's name"],
                    ['tag' => '{{verification_url}}', 'description' => 'Email verification link'],
                    ['tag' => '{{site_name}}', 'description' => 'Your site name'],
                ]),
            ],
            [
                'key' => 'welcome',
                'name' => 'Welcome Email',
                'description' => 'Sent when a new user account is created by an administrator.',
                'subject' => 'Welcome to {{site_name}} — Set up your account',
                'body' => '<p>Hello {{user_name}},</p><p>An account has been created for you on <strong>{{site_name}}</strong>. Please complete two steps to activate your account:</p><p><strong>Step 1 — Set your password:</strong></p><p><a href="{{reset_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Set Your Password</a></p><p><strong>Step 2 — Verify your email address:</strong></p><p>After setting your password and logging in, verify your email with this link:</p><p><a href="{{verification_url}}">Verify Email Address</a></p><p>Both links expire in 24 hours.</p><p>If you did not expect this invitation, you can safely ignore this email.</p><p>— {{site_name}}</p>',
                'merge_tags' => json_encode([
                    ['tag' => '{{user_name}}', 'description' => "The user's name"],
                    ['tag' => '{{reset_url}}', 'description' => 'Password setup link'],
                    ['tag' => '{{verification_url}}', 'description' => 'Email verification link'],
                    ['tag' => '{{site_name}}', 'description' => 'Your site name'],
                ]),
            ],
            [
                'key' => 'new-comment',
                'name' => 'New Comment Notification',
                'description' => 'Sent to the site admin when a new comment is posted.',
                'subject' => 'New comment on "{{post_title}}"',
                'body' => '<p>A new comment has been posted on <strong>"{{post_title}}"</strong>.</p><p><strong>Author:</strong> {{comment_author}}</p><blockquote>{{comment_body}}</blockquote><p><a href="{{post_url}}">View post</a></p><p>— {{site_name}}</p>',
                'merge_tags' => json_encode([
                    ['tag' => '{{post_title}}', 'description' => 'The blog post title'],
                    ['tag' => '{{comment_author}}', 'description' => "The commenter's name"],
                    ['tag' => '{{comment_body}}', 'description' => 'The comment text'],
                    ['tag' => '{{post_url}}', 'description' => 'Link to the blog post'],
                    ['tag' => '{{site_name}}', 'description' => 'Your site name'],
                ]),
            ],
            [
                'key' => 'comment-reply',
                'name' => 'Comment Reply Notification',
                'description' => 'Sent when someone replies to a comment.',
                'subject' => 'Someone replied to your comment on "{{post_title}}"',
                'body' => '<p>Someone replied to your comment on <strong>"{{post_title}}"</strong>.</p><p><strong>Your comment:</strong></p><blockquote>{{original_comment}}</blockquote><p><strong>{{reply_author}} replied:</strong></p><blockquote>{{reply_body}}</blockquote><p>— {{site_name}}</p>',
                'merge_tags' => json_encode([
                    ['tag' => '{{post_title}}', 'description' => 'The blog post title'],
                    ['tag' => '{{original_comment}}', 'description' => 'The original comment text'],
                    ['tag' => '{{reply_author}}', 'description' => "The replier's name"],
                    ['tag' => '{{reply_body}}', 'description' => 'The reply text'],
                    ['tag' => '{{site_name}}', 'description' => 'Your site name'],
                ]),
            ],
        ];

        $now = now();

        foreach ($templates as $template) {
            DB::table('email_templates')->insert(array_merge($template, [
                'default_subject' => $template['subject'],
                'default_body' => $template['body'],
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->whereIn('key', [
            'password-reset', 'email-verification', 'welcome', 'new-comment', 'comment-reply',
        ])->delete();
    }
};
