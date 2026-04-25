@props([
    'variant' => 'privacy',
])

@once
    <style>
        .lc-legal-visual {
            position: relative;
            min-height: 300px;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fbfa;
            border: 1px solid rgba(255, 255, 255, .28);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
        }

        .lc-legal-doc {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 2;
            width: 210px;
            min-height: 210px;
            transform: translate(-50%, -50%);
            border-radius: 8px;
            background: #fff;
            border: 1px solid #d9e7e3;
            padding: 22px;
        }

        .lc-legal-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #134e4a;
            background: linear-gradient(135deg, #ffd84d, #f97316);
            font-size: 22px;
            margin-bottom: 18px;
        }

        .lc-legal-line {
            height: 10px;
            border-radius: 999px;
            background: #d9e7e3;
            margin-top: 13px;
        }

        .lc-legal-pulse {
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: #0f766e;
            box-shadow: 0 0 0 8px rgba(15, 118, 110, .12);
            animation: lcLegalPulse 3s ease-in-out infinite;
        }

        .lc-legal-pulse.one { left: 82px; top: 86px; }
        .lc-legal-pulse.two { right: 96px; bottom: 76px; animation-delay: 1.1s; }

        @keyframes lcLegalPulse {
            0%, 100% { opacity: .35; transform: scale(.8); }
            50% { opacity: 1; transform: scale(1.12); }
        }

        @media (prefers-reduced-motion: reduce) {
            .lc-legal-pulse {
                animation: none;
            }
        }
    </style>
@endonce

<div class="lc-legal-visual" aria-label="{{ $variant === 'deletion' ? 'Data deletion request' : 'Privacy document' }}">
    <div class="lc-legal-pulse one"></div>
    <div class="lc-legal-pulse two"></div>
    <div class="lc-legal-doc">
        <div class="lc-legal-icon">
            <i class="lni {{ $variant === 'deletion' ? 'lni-trash-can' : 'lni-lock' }}"></i>
        </div>
        <div class="lc-legal-line" style="width: 82%"></div>
        <div class="lc-legal-line" style="width: 62%"></div>
        <div class="lc-legal-line" style="width: 72%"></div>
        <div class="lc-legal-line" style="width: 48%"></div>
    </div>
</div>
