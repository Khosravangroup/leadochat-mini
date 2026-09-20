@once
    <style>
        .lc-about-visual {
            position: relative;
            min-height: 420px;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fbfa;
            border: 1px solid rgba(255, 255, 255, .28);
            box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
        }

        .lc-about-visual::before {
            content: "";
            position: absolute;
            inset: 18px;
            border: 1px solid #d9e7e3;
            border-radius: 8px;
        }

        .lc-about-core {
            position: absolute;
            left: 50%;
            top: 48%;
            z-index: 4;
            width: 154px;
            min-height: 154px;
            transform: translate(-50%, -50%);
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #0f766e;
            box-shadow: 0 22px 44px rgba(15, 23, 42, .18);
        }

        .lc-about-core img {
            width: 62px;
            height: 62px;
            display: block;
            margin: 0 auto 10px;
        }

        .lc-about-core span {
            display: block;
            color: #fff8d7;
            font-size: 12px;
            font-weight: 900;
            text-align: center;
        }

        .lc-about-principle {
            position: absolute;
            z-index: 3;
            width: 150px;
            border-radius: 8px;
            padding: 14px;
            background: #fff;
            border: 1px solid #d9e7e3;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .1);
            animation: lcAboutFloat 5.5s ease-in-out infinite;
        }

        .lc-about-principle strong {
            display: block;
            color: #134e4a;
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .lc-about-principle span {
            display: block;
            width: 78%;
            height: 8px;
            border-radius: 999px;
            background: #8aa39d;
        }

        .lc-about-principle.one {
            left: 32px;
            top: 78px;
        }

        .lc-about-principle.two {
            right: 32px;
            top: 118px;
            animation-delay: .8s;
        }

        .lc-about-principle.three {
            left: 56px;
            bottom: 54px;
            animation-delay: 1.4s;
        }

        .lc-about-principle.four {
            right: 60px;
            bottom: 46px;
            animation-delay: 2s;
        }

        .lc-about-path {
            position: absolute;
            z-index: 2;
            height: 3px;
            background: linear-gradient(90deg, transparent, #f97316, transparent);
            border-radius: 999px;
            animation: lcAboutPulse 3s ease-in-out infinite;
        }

        .lc-about-path.one {
            left: 140px;
            top: 160px;
            width: 156px;
            transform: rotate(18deg);
        }

        .lc-about-path.two {
            right: 144px;
            top: 180px;
            width: 142px;
            transform: rotate(152deg);
            animation-delay: .8s;
        }

        .lc-about-path.three {
            left: 170px;
            bottom: 132px;
            width: 130px;
            transform: rotate(-22deg);
            animation-delay: 1.4s;
        }

        .lc-about-path.four {
            right: 170px;
            bottom: 130px;
            width: 130px;
            transform: rotate(202deg);
            animation-delay: 2s;
        }

        @keyframes lcAboutFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes lcAboutPulse {
            0%, 100% { opacity: .24; }
            45%, 65% { opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .lc-about-principle,
            .lc-about-path {
                animation: none;
            }
        }

        @media (max-width: 575px) {
            .lc-about-visual {
                min-height: 500px;
            }

            .lc-about-core {
                top: 50%;
                width: 126px;
                min-height: 126px;
            }

            .lc-about-principle {
                width: 132px;
            }

            .lc-about-principle.one { left: 22px; top: 70px; }
            .lc-about-principle.two { right: 22px; top: 120px; }
            .lc-about-principle.three { left: 22px; bottom: 58px; }
            .lc-about-principle.four { right: 22px; bottom: 96px; }
            .lc-about-path { display: none; }
        }
    </style>
@endonce

<div class="lc-about-visual" aria-label="Leadochat operating principles">
    <div class="lc-about-path one"></div>
    <div class="lc-about-path two"></div>
    <div class="lc-about-path three"></div>
    <div class="lc-about-path four"></div>

    <div class="lc-about-principle one"><strong>Messaging-first</strong><span></span></div>
    <div class="lc-about-principle two"><strong>Human + AI</strong><span></span></div>
    <div class="lc-about-principle three"><strong>Clear ownership</strong><span></span></div>
    <div class="lc-about-principle four"><strong>Measurable outcomes</strong><span></span></div>

    <div class="lc-about-core">
        <div>
            <img src="{{ asset('leadochat-site/brand-mark.svg') }}" alt="">
            <span>Operating system</span>
        </div>
    </div>
</div>
