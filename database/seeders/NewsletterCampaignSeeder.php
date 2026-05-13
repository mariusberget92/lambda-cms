<?php

namespace Database\Seeders;

use App\Models\NewsletterCampaign;
use Illuminate\Database\Seeder;

class NewsletterCampaignSeeder extends Seeder
{
    public function run(): void
    {
        NewsletterCampaign::firstOrCreate(
            ['title' => 'Welcome to Lambda CMS — Starter Campaign'],
            [
                'subject'         => "You're subscribed! Here's what's new 🎉",
                'body'            => '',
                'blocks'          => $this->blocks(),
                'sent_at'         => null,
                'scheduled_at'    => null,
                'recipients_count' => 0,
            ]
        );
    }

    private function blocks(): array
    {
        return [
            // Hero banner image
            $this->block(9001, 'image', [
                'url'     => 'https://picsum.photos/seed/lambda-cms/1200/400',
                'alt'     => 'Lambda CMS newsletter header',
                'caption' => '',
            ]),

            $this->block(9002, 'spacer', ['height' => ['default' => '8px']]),

            // Main heading
            $this->block(9003, 'heading', [
                'text'  => 'Welcome to Lambda CMS! 🎉',
                'level' => 1,
            ]),

            // Intro paragraph
            $this->block(9004, 'paragraph', [
                'content' => "Thanks for subscribing! We're thrilled to have you here. This is a starter campaign you can customise in the block editor — change the text, swap the image, and add your own sections before you send it.",
            ]),

            $this->block(9005, 'divider', []),

            // What's inside section
            $this->block(9006, 'heading', [
                'text'  => "What's inside Lambda CMS?",
                'level' => 2,
            ]),

            $this->block(9007, 'paragraph', [
                'content' => 'Here are a few of the features waiting for you:',
            ]),

            $this->block(9008, 'list', [
                'style' => 'unordered',
                'items' => [
                    ['text' => 'Drag-and-drop block editor for posts, pages, and templates'],
                    ['text' => 'Media library with auto-resize and bulk management'],
                    ['text' => 'Full comment moderation with nested replies'],
                    ['text' => 'Newsletter system with scheduling and double opt-in'],
                    ['text' => 'Headless REST API for building decoupled frontends'],
                    ['text' => 'Editorial calendar, analytics, and activity log'],
                ],
            ]),

            $this->block(9009, 'divider', []),

            // Inspiring quote
            $this->block(9010, 'quote', [
                'text'        => 'The scariest moment is always just before you start. After that, things can only get better.',
                'attribution' => 'Stephen King',
            ]),

            $this->block(9011, 'divider', []),

            // CTA block
            $this->block(9012, 'cta', [
                'headline'     => 'Ready to start writing?',
                'text'         => 'Head to your dashboard and create your first post. Your audience is waiting.',
                'button_label' => 'Go to Dashboard →',
                'button_url'   => '/dashboard',
            ]),

            $this->block(9013, 'spacer', ['height' => ['default' => '16px']]),

            // Footer note
            $this->block(9014, 'paragraph', [
                'content' => 'This email was sent because you subscribed to our newsletter. You can customise this template freely — it\'s just a starting point.',
            ]),
        ];
    }

    private function block(int $id, string $type, array $data, array $children = []): array
    {
        $b = ['id' => $id, 'type' => $type, 'data' => $data];
        if (!empty($children)) {
            $b['children'] = $children;
        }
        return $b;
    }
}
