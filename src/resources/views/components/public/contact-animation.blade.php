@once
    <style>
        .lc-contact-visual {
            position: relative;
            min-height: 420px;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fbfa;
            border: 1px solid rgba(255, 255, 255, .28);
            box-shadow: 0 28px 80px rgba(15, 23, 42, .2);
        }

        .lc-contact-card {
            position: absolute;
            left: 42px;
            right: 42px;
            top: 56px;
            z-index: 3;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #d9e7e3;
            padding: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .1);
        }

        .lc-contact-card strong {
            display: block;
            color: #134e4a;
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 14px;
        }

        .lc-contact-field {
            height: 12px;
            border-radius: 999px;
            background: #d9e7e3;
            margin-top: 13px;
        }

        .lc-contact-field.short {
            width: 54%;
            background: #8aa39d;
        }

        .lc-contact-route {
            position: absolute;
            left: 50%;
            top: 248px;
            width: 4px;
            height: 84px;
            border-radius: 999px;
            background: linear-gradient(180deg, #ffd84d, #f97316);
            transform: translateX(-50%);
            animation: lcContactRoute 3s ease-in-out infinite;
        }

        .lc-contact-agent {
            position: absolute;
            bottom: 42px;
            width: 142px;
            border-radius: 8px;
            background: #fff8d7;
            border: 1px solid #f6c55b;
            padding: 14px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .1);
            animation: lcContactFloat 5s ease-in-out infinite;
        }

        .lc-contact-agent.one {
            left: 52px;
        }

        .lc-contact-agent.two {
            right: 52px;
            animation-delay: 1s;
        }

        .lc-contact-agent i {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            color: #fff8d7;
            background: #0f766e;
            margin-bottom: 10px;
        }

        .lc-contact-agent strong {
            display: block;
            color: #134e4a;
            font-size: 13px;
            font-weight: 900;
        }

        @keyframes lcContactRoute {
            0%, 100% { transform: translateX(-50%) scaleY(.35); transform-origin: top; opacity: .45; }
            50% { transform: translateX(-50%) scaleY(1); opacity: 1; }
        }

        @keyframes lcContactFloat {
            0%, 100% { translate: 0 0; }
            50% { translate: 0 -8px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .lc-contact-route,
            .lc-contact-agent {
                animation: none;
            }
        }

        @media (max-width: 575px) {
            .lc-contact-visual {
                min-height: 500px;
            }

            .lc-contact-card {
                left: 24px;
                right: 24px;
                top: 36px;
            }

            .lc-contact-agent {
                width: 124px;
                bottom: 42px;
            }

            .lc-contact-agent.one { left: 28px; }
            .lc-contact-agent.two { right: 28px; }
        }
    </style>
@endonce

<div class="lc-contact-visual" aria-label="Leadochat demo request routing">
    <div class="lc-contact-card">
        <strong>New workflow request</strong>
        <div class="lc-contact-field"></div>
        <div class="lc-contact-field short"></div>
        <div class="lc-contact-field" style="width:82%"></div>
    </div>
    <div class="lc-contact-route"></div>
    <div class="lc-contact-agent one"><i class="lni lni-comments"></i><strong>Messaging review</strong></div>
    <div class="lc-contact-agent two"><i class="lni lni-cog"></i><strong>Setup plan</strong></div>
</div>
