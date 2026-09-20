@props([
    'label' => 'Feature map',
])

@once
    <style>
        .lc-features-visual {
            position: relative;
            min-height: 430px;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fbfa;
            border: 1px solid rgba(255, 255, 255, .28);
            box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
            isolation: isolate;
        }

        .lc-features-visual::before {
            content: "";
            position: absolute;
            inset: 18px;
            border: 1px solid #d9e7e3;
            border-radius: 8px;
        }

        .lc-features-label {
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

        .lc-features-label::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #f97316;
        }

        .lc-feature-core {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 4;
            width: 132px;
            height: 132px;
            transform: translate(-50%, -50%);
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #0f766e;
            box-shadow: 0 22px 44px rgba(15, 23, 42, .18);
        }

        .lc-feature-core img {
            width: 58px;
            height: 58px;
            display: block;
            margin: 0 auto 8px;
        }

        .lc-feature-core span {
            display: block;
            color: #fff8d7;
            font-size: 12px;
            font-weight: 900;
            text-align: center;
        }

        .lc-feature-rings {
            position: absolute;
            inset: 72px 44px 44px;
        }

        .lc-feature-ring {
            position: absolute;
            left: 50%;
            top: 50%;
            border-radius: 999px;
            border: 1px solid #d9e7e3;
            transform: translate(-50%, -50%);
        }

        .lc-feature-ring.one {
            width: 210px;
            height: 210px;
        }

        .lc-feature-ring.two {
            width: 318px;
            height: 318px;
        }

        .lc-feature-orbit {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 318px;
            height: 318px;
            transform: translate(-50%, -50%);
            animation: lcFeatureOrbit 24s linear infinite;
        }

        .lc-feature-node {
            position: absolute;
            z-index: 3;
            width: 116px;
            min-height: 70px;
            border-radius: 8px;
            padding: 12px;
            background: #fff;
            border: 1px solid #d9e7e3;
            color: #134e4a;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .1);
            animation: lcFeatureCounter 24s linear infinite;
        }

        .lc-feature-node i {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            margin-bottom: 8px;
            color: #134e4a;
            background: linear-gradient(135deg, #ffd84d, #f97316);
            font-size: 15px;
        }

        .lc-feature-node span {
            display: block;
            font-size: 12px;
            line-height: 1.25;
            font-weight: 900;
        }

        .lc-feature-node.one {
            left: 101px;
            top: -16px;
        }

        .lc-feature-node.two {
            right: -8px;
            top: 76px;
        }

        .lc-feature-node.three {
            right: 20px;
            bottom: 18px;
        }

        .lc-feature-node.four {
            left: 18px;
            bottom: 18px;
        }

        .lc-feature-node.five {
            left: -8px;
            top: 76px;
        }

        .lc-feature-node.six {
            left: 101px;
            bottom: -16px;
        }

        .lc-feature-signal {
            position: absolute;
            z-index: 2;
            height: 8px;
            width: 8px;
            border-radius: 999px;
            background: #f97316;
            box-shadow: 0 0 0 7px rgba(249, 115, 22, .14);
            animation: lcFeatureSignal 3.2s ease-in-out infinite;
        }

        .lc-feature-signal.a {
            left: 30%;
            top: 35%;
        }

        .lc-feature-signal.b {
            right: 28%;
            top: 61%;
            animation-delay: 1s;
        }

        .lc-feature-signal.c {
            left: 46%;
            bottom: 22%;
            animation-delay: 1.8s;
        }

        .lc-feature-side-panel {
            position: absolute;
            right: 26px;
            bottom: 24px;
            z-index: 5;
            width: 150px;
            border-radius: 8px;
            background: #fff8d7;
            border: 1px solid #f6c55b;
            padding: 14px;
        }

        .lc-feature-side-panel strong {
            display: block;
            color: #134e4a;
            font-size: 22px;
            line-height: 1;
            margin-bottom: 9px;
        }

        .lc-feature-side-panel span {
            display: block;
            width: 82%;
            height: 8px;
            border-radius: 999px;
            background: #8aa39d;
        }

        @keyframes lcFeatureOrbit {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        @keyframes lcFeatureCounter {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }

        @keyframes lcFeatureSignal {
            0%, 100% {
                opacity: .35;
                transform: scale(.85);
            }
            45%, 65% {
                opacity: 1;
                transform: scale(1.15);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .lc-feature-orbit,
            .lc-feature-node,
            .lc-feature-signal {
                animation: none;
            }
        }

        @media (max-width: 575px) {
            .lc-features-visual {
                min-height: 520px;
            }

            .lc-feature-rings {
                inset: 82px 20px 34px;
            }

            .lc-feature-orbit {
                width: 260px;
                height: 260px;
            }

            .lc-feature-ring.one {
                width: 174px;
                height: 174px;
            }

            .lc-feature-ring.two {
                width: 260px;
                height: 260px;
            }

            .lc-feature-core {
                width: 108px;
                height: 108px;
            }

            .lc-feature-node {
                width: 96px;
                min-height: 64px;
                padding: 10px;
            }

            .lc-feature-node.one {
                left: 82px;
                top: -14px;
            }

            .lc-feature-node.six {
                left: 82px;
                bottom: -14px;
            }

            .lc-feature-side-panel {
                left: 24px;
                right: auto;
                bottom: 22px;
            }
        }
    </style>
@endonce

<div class="lc-features-visual" aria-label="{{ $label }}">
    <div class="lc-features-label">{{ $label }}</div>
    <div class="lc-feature-rings">
        <div class="lc-feature-ring one"></div>
        <div class="lc-feature-ring two"></div>
        <div class="lc-feature-signal a"></div>
        <div class="lc-feature-signal b"></div>
        <div class="lc-feature-signal c"></div>

        <div class="lc-feature-orbit">
            <div class="lc-feature-node one"><i class="lni lni-inbox"></i><span>Inbox</span></div>
            <div class="lc-feature-node two"><i class="lni lni-bolt"></i><span>Automation</span></div>
            <div class="lc-feature-node three"><i class="lni lni-cart"></i><span>Commerce</span></div>
            <div class="lc-feature-node four"><i class="lni lni-bar-chart"></i><span>Analytics</span></div>
            <div class="lc-feature-node five"><i class="lni lni-users"></i><span>Team ops</span></div>
            <div class="lc-feature-node six"><i class="lni lni-plug"></i><span>Integrations</span></div>
        </div>

        <div class="lc-feature-core">
            <div>
                <img src="{{ asset('leadochat-site/brand-mark.svg') }}" alt="">
                <span>Leadochat</span>
            </div>
        </div>
    </div>
    <div class="lc-feature-side-panel">
        <strong>360°</strong>
        <span></span>
    </div>
</div>
