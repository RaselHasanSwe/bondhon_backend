<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title'            => 'Find Your Perfect Life Partner',
                'slug'             => 'home_hero',
                'content'          => '<p>Join <strong>thousands of verified profiles</strong> and let our smart matching algorithm find your ideal match. Registration is completely free.</p><div class="home-stats" style="display:flex;gap:2rem;margin-top:1.5rem;flex-wrap:wrap;"><div style="text-align:center"><strong style="font-size:1.5rem;color:#C9A227;">50,000+</strong><div style="font-size:.85rem;color:#6B7280;">Profiles</div></div><div style="text-align:center"><strong style="font-size:1.5rem;color:#C9A227;">10,000+</strong><div style="font-size:.85rem;color:#6B7280;">Matches Made</div></div><div style="text-align:center"><strong style="font-size:1.5rem;color:#C9A227;">4.9 ★</strong><div style="font-size:.85rem;color:#6B7280;">Rating</div></div></div>',
                'meta_title'       => "Bangladesh's Most Trusted Matrimony Platform",
                'meta_description' => 'Find your perfect life partner on My Bouma — Bangladesh\'s most trusted premium matrimony platform.',
                'is_published'     => true,
                'sort_order'       => 1,
            ],
            [
                'title'            => 'About Us',
                'slug'             => 'about',
                'content'          => '<h2>About MyBouma</h2><p>MyBouma is a premium matrimony platform dedicated to helping people find meaningful, lasting relationships. Founded with a vision to modernize the matchmaking process in Bangladesh, we combine traditional values with modern technology.</p><h3>Our Mission</h3><p>Our mission is to help every individual find a compatible life partner in a safe, respectful, and dignified manner. We believe every person deserves to find their perfect match.</p><h3>Why Choose Us?</h3><ul><li>Verified profiles for safety and trust</li><li>Advanced matching algorithm</li><li>Privacy-first approach</li><li>Dedicated customer support</li></ul>',
                'meta_title'       => 'About Us — My Bouma Matrimony',
                'meta_description' => 'Learn about My Bouma — our mission, values, and commitment to helping you find the perfect life partner.',
                'is_published'     => true,
                'sort_order'       => 2,
            ],
            [
                'title'            => 'Frequently Asked Questions',
                'slug'             => 'faq',
                'content'          => '<div class="faq-item"><h3>How does My Bouma work?</h3><p>My Bouma uses an advanced matching algorithm to suggest compatible profiles based on your preferences, lifestyle, religion, education, and more.</p></div><div class="faq-item"><h3>Is my personal information safe?</h3><p>Yes, we take privacy very seriously. Your contact information is never shared without your consent, and all profiles are verified by our team.</p></div><div class="faq-item"><h3>How do I create a profile?</h3><p>Simply register with your email, complete your profile with basic information, upload photos, set your partner preferences, and start browsing matches.</p></div><div class="faq-item"><h3>What subscription plans are available?</h3><p>We offer Free, Silver, Gold, and Platinum plans with varying features. Check our subscription page for detailed plan comparisons.</p></div><div class="faq-item"><h3>How can I contact support?</h3><p>You can reach our support team at support@mybouma.com or use the contact form on our Contact Us page.</p></div>',
                'meta_title'       => 'FAQ — My Bouma Matrimony',
                'meta_description' => 'Frequently asked questions about My Bouma matrimony platform — how it works, privacy, subscriptions, and more.',
                'is_published'     => true,
                'sort_order'       => 3,
            ],
            [
                'title'            => 'Terms & Conditions',
                'slug'             => 'terms',
                'content'          => '<h2>Terms &amp; Conditions</h2><p><strong>Last updated: May 2026</strong></p><p>By accessing or using My Bouma, you agree to be bound by these Terms and Conditions. Please read them carefully.</p><h3>1. Eligibility</h3><p>You must be at least 18 years of age to register and use My Bouma. By registering, you confirm that you are of legal age and have the legal capacity to enter into a binding agreement.</p><h3>2. Account Responsibility</h3><p>You are responsible for maintaining the confidentiality of your account credentials. You agree to notify us immediately of any unauthorized use of your account.</p><h3>3. Prohibited Conduct</h3><p>Users may not: post false or misleading information, harass other members, use the platform for commercial purposes without permission, or engage in any illegal activities.</p><h3>4. Content Policy</h3><p>All profile photos must be of yourself and must be appropriate. Inappropriate content will be removed and may result in account suspension.</p><h3>5. Subscription & Payments</h3><p>Subscription fees are non-refundable except as required by applicable law. Plan features are clearly described on our subscription page.</p><h3>6. Privacy</h3><p>Your use of My Bouma is also governed by our Privacy Policy. Please review it to understand our practices.</p><h3>7. Termination</h3><p>We reserve the right to suspend or terminate accounts that violate these terms.</p><h3>8. Contact</h3><p>For questions about these terms, contact us at support@mybouma.com.</p>',
                'meta_title'       => 'Terms & Conditions — My Bouma Matrimony',
                'meta_description' => 'Read the Terms & Conditions for using My Bouma matrimony platform. Understand your rights and responsibilities.',
                'is_published'     => true,
                'sort_order'       => 4,
            ],
            [
                'title'            => 'Privacy Policy',
                'slug'             => 'privacy_policy',
                'content'          => '<h2>Privacy Policy</h2><p><strong>Last updated: May 2026</strong></p><p>My Bouma is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information.</p><h3>1. Information We Collect</h3><p>We collect information you provide during registration (name, email, gender), profile information (age, location, religion, education), photos you upload, and usage data.</p><h3>2. How We Use Your Information</h3><p>We use your information to: provide and improve our services, show relevant matches, send notifications, and communicate with you about your account.</p><h3>3. Information Sharing</h3><p>We do not sell your personal information. Your contact details are only shared with other users according to your privacy settings.</p><h3>4. Data Security</h3><p>We implement industry-standard security measures to protect your personal information from unauthorized access or disclosure.</p><h3>5. Your Rights</h3><p>You have the right to access, update, or delete your personal information. Contact us at privacy@mybouma.com for data requests.</p><h3>6. Cookies</h3><p>We use cookies to improve your experience. You can control cookie settings in your browser.</p><h3>7. Contact</h3><p>For privacy concerns, contact us at privacy@mybouma.com.</p>',
                'meta_title'       => 'Privacy Policy — My Bouma Matrimony',
                'meta_description' => 'Read My Bouma\'s Privacy Policy to understand how we collect, use, and protect your personal information.',
                'is_published'     => true,
                'sort_order'       => 5,
            ],
            [
                'title'            => 'Contact Information',
                'slug'             => 'contact_info',
                'content'          => '<p>We are here to help you. Reach out to our support team through any of the channels below.</p>',
                'meta_title'       => 'Contact Us — My Bouma Matrimony',
                'meta_description' => 'Get in touch with My Bouma support. We are here to help you find your perfect match.',
                'is_published'     => true,
                'sort_order'       => 6,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}

