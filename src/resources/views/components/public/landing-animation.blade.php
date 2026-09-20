@props([
    'variant' => 'omnichannel-inbox',
    'label' => 'Workflow',
])

@php
    $config = [
        'omnichannel-inbox' => [
            'icon' => 'lni-inbox',
            'title' => 'One history',
            'nodes' => ['WhatsApp', 'Instagram', 'Telegram', 'Live chat'],
            'accent' => '#0f766e',
        ],
        'instagram-dm-automation' => [
            'icon' => 'lni-instagram',
            'title' => 'Comment to DM',
            'nodes' => ['Post', 'Comment', 'DM', 'Sale'],
            'accent' => '#f97316',
        ],
        'whatsapp-business-automation' => [
            'icon' => 'lni-whatsapp',
            'title' => 'WhatsApp flow',
            'nodes' => ['Lead', 'Qualify', 'Reminder', 'Agent'],
            'accent' => '#22c55e',
        ],
        'facebook-messenger-automation' => [
            'icon' => 'lni-facebook',
            'title' => 'Messenger',
            'nodes' => ['Page', 'Reply', 'Profile', 'Follow-up'],
            'accent' => '#0ea5e9',
        ],
        'customer-analytics' => [
            'icon' => 'lni-bar-chart',
            'title' => 'Revenue insight',
            'nodes' => ['Channel', 'Campaign', 'Agent', 'Revenue'],
            'accent' => '#f97316',
        ],
        'whatsapp-sales-funnel' => [
            'icon' => 'lni-funnel',
            'title' => 'Sales funnel',
            'nodes' => ['Capture', 'Nurture', 'Handoff', 'Close'],
            'accent' => '#22c55e',
        ],
        'shared-inbox-customer-support' => [
            'icon' => 'lni-support',
            'title' => 'Support queue',
            'nodes' => ['Assign', 'Note', 'Escalate', 'Resolve'],
            'accent' => '#0f766e',
        ],
    ][$variant] ?? [
        'icon' => 'lni-comments',
        'title' => 'Workflow',
        'nodes' => ['Capture', 'Route', 'Reply', 'Measure'],
        'accent' => '#0f766e',
    ];
@endphp

@once
    <style>
        .lc-landing-visual {
            position: relative;
            min-height: 430px;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fbfa;
            border: 1px solid rgba(255, 255, 255, .28);
            box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
        }

        .lc-landing-visual::before {
            content: "";
            position: absolute;
            inset: 18px;
            border: 1px solid #d9e7e3;
            border-radius: 8px;
        }

        .lc-landing-label {
            position: absolute;
            left: 28px;
            top: 26px;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            border-radius: 8px;
            padding: 0 12px;
            color: #134e4a;
            background: #fff8d7;
            font-size: 13px;
            font-weight: 900;
        }

        .lc-landing-label::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--landing-accent, #0f766e);
        }

        .lc-landing-core {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 4;
            width: 138px;
            min-height: 138px;
            transform: translate(-50%, -50%);
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #fff8d7;
            background: #134e4a;
            box-shadow: 0 22px 44px rgba(15, 23, 42, .18);
        }

        .lc-landing-core i {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            border-radius: 8px;
            color: #134e4a;
            background: linear-gradient(135deg, #ffd84d, #f97316);
            font-size: 24px;
            margin: 0 auto 10px;
        }

        .lc-landing-core span {
            display: block;
            font-size: 12px;
            font-weight: 900;
            text-align: center;
        }

        .lc-landing-track {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 320px;
            height: 320px;
            transform: translate(-50%, -50%);
        }

        .lc-landing-node {
            position: absolute;
            z-index: 3;
            width: 118px;
            min-height: 64px;
            border-radius: 8px;
            padding: 13px;
            color: #134e4a;
            background: #fff;
            border: 1px solid #d9e7e3;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .1);
            animation: lcLandingFloat 5s ease-in-out infinite;
        }

        .lc-landing-node strong {
            display: block;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .lc-landing-node span {
            display: block;
            width: 72%;
            height: 8px;
            border-radius: 999px;
            background: #8aa39d;
        }

        .lc-landing-node.one { left: 101px; top: -16px; }
        .lc-landing-node.two { right: -12px; top: 126px; animation-delay: .8s; }
        .lc-landing-node.three { left: 101px; bottom: -16px; animation-delay: 1.6s; }
        .lc-landing-node.four { left: -12px; top: 126px; animation-delay: 2.4s; }

        .lc-landing-line {
            position: absolute;
            z-index: 2;
            left: 50%;
            top: 50%;
            width: 132px;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, transparent, var(--landing-accent, #0f766e), transparent);
            transform-origin: left center;
            animation: lcLandingPulse 3s ease-in-out infinite;
        }

        .lc-landing-line.one { transform: rotate(-90deg); }
        .lc-landing-line.two { transform: rotate(0deg); animation-delay: .8s; }
        .lc-landing-line.three { transform: rotate(90deg); animation-delay: 1.6s; }
        .lc-landing-line.four { transform: rotate(180deg); animation-delay: 2.4s; }

        @keyframes lcLandingFloat {
            0%, 100% { translate: 0 0; }
            50% { translate: 0 -8px; }
        }

        @keyframes lcLandingPulse {
            0%, 100% { opacity: .2; }
            45%, 65% { opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .lc-landing-node,
            .lc-landing-line {
                animation: none;
            }
        }

        @media (max-width: 575px) {
            .lc-landing-visual {
                min-height: 520px;
            }

            .lc-landing-track {
                width: 260px;
                height: 300px;
            }

            .lc-landing-node {
                width: 104px;
            }

            .lc-landing-node.one { left: 78px; top: -8px; }
            .lc-landing-node.two { right: -6px; top: 118px; }
            .lc-landing-node.three { left: 78px; bottom: -8px; }
            .lc-landing-node.four { left: -6px; top: 118px; }
            .lc-landing-line { display: none; }
        }
    </style>
@endonce

<div class="lc-landing-visual" aria-label="{{ $label }}" style="--landing-accent: {{ $config['accent'] }}">
    <div class="lc-landing-label">{{ $label }}</div>
    <div class="lc-landing-track">
        <div class="lc-landing-line one"></div>
        <div class="lc-landing-line two"></div>
        <div class="lc-landing-line three"></div>
        <div class="lc-landing-line four"></div>

        <div class="lc-landing-node one"><strong>{{ $config['nodes'][0] }}</strong><span></span></div>
        <div class="lc-landing-node two"><strong>{{ $config['nodes'][1] }}</strong><span></span></div>
        <div class="lc-landing-node three"><strong>{{ $config['nodes'][2] }}</strong><span></span></div>
        <div class="lc-landing-node four"><strong>{{ $config['nodes'][3] }}</strong><span></span></div>

        <div class="lc-landing-core">
            <div>
                <i class="lni {{ $config['icon'] }}"></i>
                <span>{{ $config['title'] }}</span>
            </div>
        </div>
    </div>
</div>
