@props([
    'label' => 'Customer operations live',
])

@once
    <style>
        .lc-conversation-visual {
            position: relative;
            min-height: 430px;
            border-radius: 8px;
            overflow: hidden;
            background: linear-gradient(135deg, #0f766e 0%, #134e4a 100%);
            box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
            isolation: isolate;
        }

        .lc-conversation-visual::before {
            content: "";
            position: absolute;
            inset: 18px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            pointer-events: none;
        }

        .lc-visual-topbar {
            position: absolute;
            left: 30px;
            right: 30px;
            top: 28px;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .lc-visual-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            border-radius: 8px;
            padding: 0 12px;
            color: #fff8d7;
            background: rgba(255, 255, 255, .12);
            font-size: 13px;
            font-weight: 800;
        }

        .lc-visual-label::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #ffd84d;
        }

        .lc-visual-status {
            min-height: 30px;
            border-radius: 8px;
            padding: 7px 10px;
            background: #fff8d7;
            color: #134e4a;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .lc-ops-board {
            position: absolute;
            left: 30px;
            right: 30px;
            top: 82px;
            bottom: 28px;
            z-index: 2;
            display: grid;
            grid-template-columns: minmax(112px, .75fr) minmax(160px, 1fr) minmax(118px, .8fr);
            gap: 12px;
        }

        .lc-ops-column,
        .lc-ops-center {
            border-radius: 8px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
            overflow: hidden;
        }

        .lc-ops-column {
            padding: 12px;
        }

        .lc-ops-heading {
            width: 70%;
            height: 10px;
            border-radius: 999px;
            background: #134e4a;
            margin-bottom: 14px;
        }

        .lc-ops-thread {
            position: relative;
            display: grid;
            gap: 10px;
        }

        .lc-ops-message {
            min-height: 50px;
            border-radius: 8px;
            background: #f3faf8;
            border: 1px solid #d9e7e3;
            padding: 10px;
            animation: lcMessageFocus 6s ease-in-out infinite;
        }

        .lc-ops-message:nth-child(2) {
            animation-delay: 1.3s;
        }

        .lc-ops-message:nth-child(3) {
            animation-delay: 2.6s;
        }

        .lc-ops-message:nth-child(4) {
            animation-delay: 3.9s;
        }

        .lc-ops-avatar {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #f97316;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }

        .lc-ops-line {
            display: inline-block;
            height: 8px;
            border-radius: 999px;
            background: #8aa39d;
            vertical-align: middle;
        }

        .lc-ops-line.long {
            width: 72%;
            margin-top: 10px;
        }

        .lc-ops-line.short {
            width: 44%;
            background: #0f766e;
        }

        .lc-ops-center {
            display: grid;
            grid-template-rows: 54px 1fr 72px;
        }

        .lc-ops-center-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px;
            background: #fff8d7;
            color: #134e4a;
            font-weight: 900;
            font-size: 13px;
        }

        .lc-ops-brand {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #fff;
            display: grid;
            place-items: center;
        }

        .lc-ops-brand img {
            width: 20px;
            height: 20px;
        }

        .lc-automation-track {
            position: relative;
            display: grid;
            align-content: center;
            gap: 12px;
            padding: 18px;
        }

        .lc-automation-step {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            border-radius: 8px;
            padding: 9px 10px;
            color: #134e4a;
            background: #f7fbfa;
            border: 1px solid #d9e7e3;
            font-size: 12px;
            font-weight: 900;
        }

        .lc-automation-step i {
            width: 22px;
            height: 22px;
            border-radius: 8px;
            background: #0f766e;
            color: #fff;
            display: grid;
            place-items: center;
            font-style: normal;
            font-size: 11px;
        }

        .lc-automation-step.is-gold i {
            background: #f97316;
        }

        .lc-automation-pulse {
            position: absolute;
            left: 28px;
            top: 52px;
            width: 6px;
            height: 54%;
            border-radius: 999px;
            background: linear-gradient(180deg, #ffd84d, #f97316);
            animation: lcTrackPulse 4s ease-in-out infinite;
        }

        .lc-ops-reply {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px;
            border-top: 1px solid #d9e7e3;
            background: #fff;
        }

        .lc-reply-field {
            flex: 1;
            height: 36px;
            border-radius: 8px;
            background: #f3faf8;
        }

        .lc-reply-button {
            width: 38px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, #ffd84d, #f97316);
        }

        .lc-metric-stack {
            display: grid;
            gap: 10px;
        }

        .lc-mini-metric {
            min-height: 68px;
            border-radius: 8px;
            background: #f3faf8;
            border: 1px solid #d9e7e3;
            padding: 12px;
        }

        .lc-mini-metric strong {
            display: block;
            color: #134e4a;
            font-size: 20px;
            line-height: 1;
            margin-bottom: 8px;
        }

        .lc-mini-metric span {
            display: block;
            width: 72%;
            height: 8px;
            border-radius: 999px;
            background: #8aa39d;
        }

        @keyframes lcMessageFocus {
            0%, 100% {
                transform: translateY(0);
                background: #f3faf8;
                border-color: #d9e7e3;
            }
            42%, 58% {
                transform: translateY(-3px);
                background: #fff8d7;
                border-color: #f6c55b;
            }
        }

        @keyframes lcTrackPulse {
            0%, 100% {
                transform: scaleY(.35);
                transform-origin: top;
                opacity: .55;
            }
            45%, 60% {
                transform: scaleY(1);
                opacity: 1;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .lc-ops-message,
            .lc-automation-pulse {
                animation: none;
            }
        }

        @media (max-width: 575px) {
            .lc-conversation-visual {
                min-height: 560px;
            }

            .lc-visual-topbar {
                left: 20px;
                right: 20px;
                top: 20px;
                align-items: flex-start;
                flex-direction: column;
            }

            .lc-ops-board {
                left: 20px;
                right: 20px;
                top: 104px;
                bottom: 20px;
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr auto;
            }

            .lc-ops-column:last-child {
                display: none;
            }
        }
    </style>
@endonce

<div class="lc-conversation-visual" aria-label="{{ $label }}">
    <div class="lc-visual-topbar">
        <div class="lc-visual-label">{{ $label }}</div>
        <div class="lc-visual-status">24 live conversations</div>
    </div>

    <div class="lc-ops-board">
        <div class="lc-ops-column">
            <div class="lc-ops-heading"></div>
            <div class="lc-ops-thread">
                <div class="lc-ops-message">
                    <span class="lc-ops-avatar"></span><span class="lc-ops-line short"></span>
                    <span class="lc-ops-line long"></span>
                </div>
                <div class="lc-ops-message">
                    <span class="lc-ops-avatar" style="background:#14b8a6"></span><span class="lc-ops-line short"></span>
                    <span class="lc-ops-line long"></span>
                </div>
                <div class="lc-ops-message">
                    <span class="lc-ops-avatar" style="background:#0ea5e9"></span><span class="lc-ops-line short"></span>
                    <span class="lc-ops-line long"></span>
                </div>
                <div class="lc-ops-message">
                    <span class="lc-ops-avatar" style="background:#22c55e"></span><span class="lc-ops-line short"></span>
                    <span class="lc-ops-line long"></span>
                </div>
            </div>
        </div>

        <div class="lc-ops-center">
            <div class="lc-ops-center-head">
                <span class="lc-ops-brand">
                    <img src="{{ asset('leadochat-site/brand-mark.svg') }}" alt="">
                </span>
                <span>Lead captured</span>
            </div>
            <div class="lc-automation-track">
                <div class="lc-automation-pulse"></div>
                <div class="lc-automation-step"><i>1</i><span>Qualify</span></div>
                <div class="lc-automation-step is-gold"><i>2</i><span>Route</span></div>
                <div class="lc-automation-step"><i>3</i><span>Follow up</span></div>
                <div class="lc-automation-step is-gold"><i>4</i><span>Convert</span></div>
            </div>
            <div class="lc-ops-reply">
                <div class="lc-reply-field"></div>
                <div class="lc-reply-button"></div>
            </div>
        </div>

        <div class="lc-ops-column">
            <div class="lc-ops-heading"></div>
            <div class="lc-metric-stack">
                <div class="lc-mini-metric"><strong>82%</strong><span></span></div>
                <div class="lc-mini-metric"><strong>4.8x</strong><span></span></div>
                <div class="lc-mini-metric"><strong>18m</strong><span></span></div>
            </div>
        </div>
    </div>
</div>
