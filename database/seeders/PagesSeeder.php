<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/** Starter content for the public site. Only creates pages that do not exist yet. */
class PagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['about', 'About us', 1, true, false, "## Welcome\n\nWe are a hotel that cares about the small details: clean rooms, honest prices and friendly service.\n\nEdit this page in the back office under **Website pages** to tell your own story."],
            ['terms', 'Terms of service', 90, false, true, "## Terms of service\n\nBy making a booking you agree to the booking conditions shown for each room type, including the check-in and check-out times and the cancellation rules.\n\nReplace this text with your own terms."],
            ['privacy', 'Privacy policy', 91, false, true, "## Privacy policy\n\nWe only use the personal details you give us to manage your booking and to contact you about it. We do not sell your data.\n\nReplace this text with your own privacy policy."],
        ];

        foreach ($pages as [$slug, $title, $sort, $menu, $footer, $body]) {
            Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'sort' => $sort, 'show_in_menu' => $menu, 'show_in_footer' => $footer, 'published' => true, 'body' => $body]);
        }
    }
}
