<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function home(): View
    {
        return view('public.home');
    }

    public function features(): View
    {
        return view('public.features');
    }

    public function pricing(): View
    {
        return view('public.pricing');
    }

    public function landingPage(string $slug): View
    {
        abort_unless(array_key_exists($slug, $this->landingPages()), 404);

        return view('public.landing-page', [
            'page' => $this->landingPages()[$slug],
            'slug' => $slug,
        ]);
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function privacyPolicy(): View
    {
        return view('public.privacy-policy');
    }

    public function dataDeletion(): View
    {
        return view('public.data-deletion');
    }

    public function contact(): View
    {
        return view('public.contact');
    }

    protected function landingPages(): array
    {
        return [
            'omnichannel-inbox' => [
                'title' => 'Omnichannel Inbox for Sales & Support | Leadochat',
                'meta' => 'Centralize every customer message in one inbox. Reduce missed leads, speed up replies, and give every agent full customer context across channels.',
                'eyebrow' => 'Omnichannel Inbox',
                'h1' => 'One Inbox for WhatsApp, Instagram, Telegram, Facebook, and Live Chat',
                'intro' => 'Give your team one shared workspace for every customer conversation, every channel, and every handoff.',
                'primaryCta' => 'See the unified inbox in action',
                'secondaryCta' => 'Explore Instagram automation',
                'secondaryRoute' => 'public.instagram-dm-automation',
                'proof' => ['One history per customer', 'Shared ownership', 'Notes and assignments', 'Media and context'],
                'sections' => [
                    ['title' => 'Stop losing leads between apps', 'body' => 'Bring WhatsApp, Instagram, Telegram, Facebook, and live chat into one queue so every message has an owner and a visible history.'],
                    ['title' => 'Give agents the full customer story', 'body' => 'Show profile details, previous conversations, tags, notes, and channel history beside every chat so replies are faster and more useful.'],
                    ['title' => 'Move from inbox to action', 'body' => 'Route conversations, assign agents, collaborate internally, and connect automation when a human reply is not the best next step.'],
                ],
                'features' => ['Unified multi-channel conversation list', 'Conversation assignment and team notes', 'Full customer profile beside each chat', 'Media history and social message context', 'Realtime updates for active conversations', 'Automation, analytics, and CRM context in the same platform'],
                'faq' => [
                    ['q' => 'What is an omnichannel inbox?', 'a' => 'An omnichannel inbox centralizes messages from multiple channels into one shared team workspace with one history per customer.'],
                    ['q' => 'Can sales and support teams use the same inbox?', 'a' => 'Yes. Teams can use assignments, notes, tags, and customer context to separate ownership while keeping the full conversation history visible.'],
                ],
            ],
            'instagram-dm-automation' => [
                'title' => 'Instagram DM Automation & Comment Management | Leadochat',
                'meta' => 'Automate Instagram replies, manage DMs, capture leads from comments, and turn social engagement into sales from one platform.',
                'eyebrow' => 'Instagram Automation',
                'h1' => 'Convert Instagram Comments and DMs into Customers',
                'intro' => 'Capture intent from posts, comments, stories, and DMs, then route each lead into automated follow-up or a human conversation.',
                'primaryCta' => 'See Instagram automation examples',
                'secondaryCta' => 'View the inbox',
                'secondaryRoute' => 'public.omnichannel-inbox',
                'proof' => ['Comment-to-DM flows', 'DM inbox for teams', 'Story reply context', 'Lead follow-up'],
                'sections' => [
                    ['title' => 'Turn engagement into qualified conversations', 'body' => 'Use comments, DMs, and story replies as lead signals, then guide customers toward the next action without losing the social context.'],
                    ['title' => 'Manage Instagram like a sales channel', 'body' => 'Moderate comments, reply by DM, handle inbound media, and keep every customer interaction visible in one workspace.'],
                    ['title' => 'Hand off to humans when it matters', 'body' => 'Let automation handle capture and follow-up, while agents step in with the full post, comment, and customer history.'],
                ],
                'features' => ['Instagram DM management', 'Comment moderation and reply workflows', 'Reply-to-comment via DM context', 'Story reply thumbnails and conversation creation', 'Customer profile and social history', 'Realtime inbox and social updates'],
                'faq' => [
                    ['q' => 'Can Leadochat manage Instagram comments and DMs together?', 'a' => 'Yes. Instagram comments, DMs, story replies, and related conversation context are designed to work from the same social and inbox workspace.'],
                    ['q' => 'Is this only a chatbot tool?', 'a' => 'No. The Instagram flow combines automation, shared inbox handling, human handoff, and customer context.'],
                ],
            ],
            'whatsapp-business-automation' => [
                'title' => 'WhatsApp Business Automation Platform | Leadochat',
                'meta' => 'Capture leads, automate follow-ups, run WhatsApp workflows, and manage customer conversations in one shared inbox with CRM context.',
                'eyebrow' => 'WhatsApp Automation',
                'h1' => 'Automate Sales and Support on WhatsApp',
                'intro' => 'Turn WhatsApp from a manual reply channel into a structured sales and support workflow with automation, inbox ownership, and customer context.',
                'primaryCta' => 'Request a WhatsApp demo',
                'secondaryCta' => 'Build a WhatsApp funnel',
                'secondaryRoute' => 'public.whatsapp-sales-funnel',
                'proof' => ['Lead capture', 'Shared inbox', 'Follow-up flows', 'Order context'],
                'sections' => [
                    ['title' => 'Capture and qualify every WhatsApp lead', 'body' => 'Use structured workflows to collect details, answer common questions, and route qualified prospects to the right team member.'],
                    ['title' => 'Collaborate without losing the thread', 'body' => 'Agents can work from one shared inbox with notes, assignments, and the customer history visible in every conversation.'],
                    ['title' => 'Connect commerce and operations', 'body' => 'Bring order context, campaign follow-up, and integrations into the same customer conversation flow.'],
                ],
                'features' => ['WhatsApp shared inbox', 'Automation and nurture flows', 'Customer profile and order context', 'Campaign reminders and follow-ups', 'Human handoff to agents', 'Shopify, WooCommerce, WordPress, Zapier, Make, n8n, and API-ready workflows'],
                'faq' => [
                    ['q' => 'Who is WhatsApp automation for?', 'a' => 'It is built for businesses that use WhatsApp for sales or support and need faster replies, better qualification, and shared team visibility.'],
                    ['q' => 'Does Leadochat replace human agents?', 'a' => 'No. Automation handles repetitive steps while agents keep control of high-value conversations.'],
                ],
            ],
            'facebook-messenger-automation' => [
                'title' => 'Facebook Messenger Automation | Leadochat',
                'meta' => 'Capture leads, answer questions, and manage Messenger conversations from one inbox with automation and CRM context.',
                'eyebrow' => 'Messenger Automation',
                'h1' => 'Automate Facebook Messenger for Sales and Support',
                'intro' => 'Capture conversations from Facebook pages, ads, and communities, then manage every follow-up from the same customer operations workspace.',
                'primaryCta' => 'Talk to sales',
                'secondaryCta' => 'View omnichannel inbox',
                'secondaryRoute' => 'public.omnichannel-inbox',
                'proof' => ['Page conversations', 'Fast replies', 'Shared ownership', 'CRM context'],
                'sections' => [
                    ['title' => 'Respond faster to Facebook demand', 'body' => 'Bring Messenger conversations into a shared inbox so leads and support requests do not sit unseen on a page account.'],
                    ['title' => 'Automate the first steps', 'body' => 'Capture lead details, answer common questions, and start follow-up workflows before an agent joins the conversation.'],
                    ['title' => 'Keep full context for every agent', 'body' => 'Show customer history, assignments, notes, and analytics so Messenger becomes part of the broader customer journey.'],
                ],
                'features' => ['Messenger shared inbox', 'Page and ad conversation capture', 'Automation and follow-up', 'Customer profile context', 'Team assignment and notes', 'Conversation analytics'],
                'faq' => [
                    ['q' => 'Can Facebook Messenger be handled with other channels?', 'a' => 'Yes. Messenger can be managed alongside WhatsApp, Instagram, Telegram, and live chat from one workspace.'],
                    ['q' => 'Can agents take over automated chats?', 'a' => 'Yes. The workflow is designed for human handoff when the conversation needs personal attention.'],
                ],
            ],
            'customer-analytics' => [
                'title' => 'Customer Analytics & Revenue Insights | Leadochat',
                'meta' => 'Analyze customer behavior, sales channels, and campaign performance across messaging, calls, and social commerce from one platform.',
                'eyebrow' => 'Customer Analytics',
                'h1' => 'Understand Which Channels, Customers, and Campaigns Drive Revenue',
                'intro' => 'See where conversations convert, where customers drop off, and which channels deserve more attention.',
                'primaryCta' => 'See analytics in action',
                'secondaryCta' => 'Explore automation',
                'secondaryRoute' => 'features',
                'proof' => ['Channel performance', 'Customer value signals', 'Campaign insight', 'Agent visibility'],
                'sections' => [
                    ['title' => 'See which channels create revenue', 'body' => 'Compare performance across messaging, social, calls, campaigns, and commerce touchpoints.'],
                    ['title' => 'Find customer behavior patterns', 'body' => 'Understand customer cohorts, repeat buyers, high-value conversations, and drop-off points.'],
                    ['title' => 'Turn reporting into action', 'body' => 'Use insights to adjust automation, routing, follow-up, and team performance.'],
                ],
                'features' => ['Channel performance reporting', 'Customer cohort and value signals', 'Campaign and automation analysis', 'Call-plus-chat insight layer', 'Agent performance visibility', 'Revenue-focused conversation reporting'],
                'faq' => [
                    ['q' => 'What does customer analytics measure?', 'a' => 'It measures channel performance, customer behavior, campaign outcomes, and conversation signals that help teams improve sales and support.'],
                    ['q' => 'Is analytics connected to the inbox?', 'a' => 'Yes. The goal is to connect operational conversations with measurable customer outcomes.'],
                ],
            ],
            'whatsapp-sales-funnel' => [
                'title' => 'WhatsApp Sales Funnel & Lead Nurturing | Leadochat',
                'meta' => 'Use WhatsApp to qualify prospects, send follow-ups, nurture leads, and turn conversations into revenue with automation and shared inbox workflows.',
                'eyebrow' => 'WhatsApp Sales Funnel',
                'h1' => 'Build a WhatsApp Funnel That Captures, Nurtures, and Converts Leads',
                'intro' => 'Move every WhatsApp lead through qualification, follow-up, routing, reminders, and human sales handoff.',
                'primaryCta' => 'Build your WhatsApp funnel',
                'secondaryCta' => 'WhatsApp automation',
                'secondaryRoute' => 'public.whatsapp-business-automation',
                'proof' => ['Lead capture', 'Qualification', 'Nurture reminders', 'Sales handoff'],
                'sections' => [
                    ['title' => 'Capture leads at the first message', 'body' => 'Start structured conversations from inbound WhatsApp messages, campaigns, and commerce touchpoints.'],
                    ['title' => 'Nurture prospects automatically', 'body' => 'Use reminders and follow-up flows to keep leads moving without relying on manual tracking.'],
                    ['title' => 'Hand off when the lead is ready', 'body' => 'Route qualified prospects to sales agents with the full conversation and customer profile attached.'],
                ],
                'features' => ['Lead capture workflows', 'Qualification questions', 'Automated follow-ups and reminders', 'Human handoff and pipeline movement', 'Conversion reporting', 'Commerce and CRM context'],
                'faq' => [
                    ['q' => 'What is a WhatsApp sales funnel?', 'a' => 'It is a structured path that captures a WhatsApp lead, qualifies the customer, sends follow-ups, and routes the prospect toward purchase.'],
                    ['q' => 'Can agents join the funnel?', 'a' => 'Yes. Agents can take over when automation qualifies a lead or when the customer needs a personal answer.'],
                ],
            ],
            'shared-inbox-customer-support' => [
                'title' => 'Shared Inbox for Multi-Agent Customer Support | Leadochat',
                'meta' => 'Unify support conversations across messaging channels, assign ownership, keep full customer history, and improve response times with one shared inbox.',
                'eyebrow' => 'Customer Support Inbox',
                'h1' => 'Give Your Support Team One Shared Inbox for Every Channel',
                'intro' => 'Help support teams reply faster, coordinate ownership, and keep the full customer history visible across every channel.',
                'primaryCta' => 'Watch the support workflow',
                'secondaryCta' => 'View omnichannel inbox',
                'secondaryRoute' => 'public.omnichannel-inbox',
                'proof' => ['Assignments', 'Notes', 'Escalation', 'Full history'],
                'sections' => [
                    ['title' => 'Make ownership clear', 'body' => 'Assign conversations, add notes, and keep support work visible so customers do not repeat themselves.'],
                    ['title' => 'Support every channel in one place', 'body' => 'Handle WhatsApp, Instagram, Telegram, Facebook, and live chat without switching between disconnected tools.'],
                    ['title' => 'Improve team performance', 'body' => 'Use response-time and conversation insights to spot bottlenecks and coach support teams.'],
                ],
                'features' => ['Multi-agent shared inbox', 'Assignments and private notes', 'Customer context and history', 'Escalation and handoff', 'Support analytics', 'Realtime conversation updates'],
                'faq' => [
                    ['q' => 'How does a shared inbox help support teams?', 'a' => 'It gives every agent the same customer history, clear ownership, and one place to manage replies.'],
                    ['q' => 'Can it support sales too?', 'a' => 'Yes. The same shared workspace can support service teams, sales teams, or both.'],
                ],
            ],
        ];
    }
}
