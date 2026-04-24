@props([
    'label' => 'Live customer conversations',
])

@once
    <style>
        .lc-conversation-visual {
            position: relative;
            min-height: 410px;
            border-radius: 8px;
            overflow: hidden;
            background:
                radial-gradient(circle at 18% 18%, rgba(255, 216, 77, .24), transparent 28%),
                radial-gradient(circle at 82% 16%, rgba(20, 184, 166, .24), transparent 30%),
                linear-gradient(135deg, rgba(15, 118, 110, .98), rgba(17, 94, 89, .96));
            box-shadow: 0 28px 80px rgba(15, 23, 42, .2);
        }

        .lc-conversation-visual::before {
            content: "";
            position: absolute;
            inset: 24px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
        }

        .lc-visual-label {
            position: absolute;
            left: 26px;
            top: 24px;
            z-index: 3;
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
            box-shadow: 0 0 0 6px rgba(255, 216, 77, .16);
        }

        .lc-orbit {
            position: absolute;
            inset: 74px 32px 34px;
        }

        .lc-inbox-core {
            position: absolute;
            left: 50%;
            top: 52%;
            z-index: 4;
            width: 148px;
            height: 148px;
            transform: translate(-50%, -50%);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 22px 45px rgba(15, 23, 42, .22);
            display: grid;
            place-items: center;
        }

        .lc-inbox-core img {
            width: 74px;
            height: 74px;
        }

        .lc-inbox-core span {
            display: block;
            margin-top: 8px;
            color: #0f766e;
            font-size: 12px;
            font-weight: 900;
            text-align: center;
        }

        .lc-channel-node {
            position: absolute;
            z-index: 2;
            width: 116px;
            min-height: 58px;
            border-radius: 8px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 18px 35px rgba(15, 23, 42, .16);
            color: #134e4a;
            font-size: 12px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            animation: lcFloat 5.5s ease-in-out infinite;
        }

        .lc-channel-node i {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #fff;
            font-style: normal;
            font-size: 13px;
        }

        .lc-node-instagram {
            left: 6%;
            top: 8%;
        }

        .lc-node-instagram i {
            background: linear-gradient(135deg, #f97316, #ec4899);
        }

        .lc-node-whatsapp {
            right: 4%;
            top: 16%;
            animation-delay: .8s;
        }

        .lc-node-whatsapp i {
            background: #16a34a;
        }

        .lc-node-chat {
            left: 12%;
            bottom: 9%;
            animation-delay: 1.4s;
        }

        .lc-node-chat i {
            background: #0ea5e9;
        }

        .lc-node-sales {
            right: 9%;
            bottom: 8%;
            animation-delay: 2.1s;
        }

        .lc-node-sales i {
            background: #f59e0b;
        }

        .lc-message-line {
            position: absolute;
            z-index: 1;
            height: 2px;
            transform-origin: left center;
            background: linear-gradient(90deg, transparent, rgba(255, 216, 77, .95), transparent);
            animation: lcPulse 2.8s ease-in-out infinite;
        }

        .lc-line-one {
            left: 26%;
            top: 34%;
            width: 168px;
            transform: rotate(18deg);
        }

        .lc-line-two {
            right: 26%;
            top: 38%;
            width: 150px;
            transform: rotate(158deg);
            animation-delay: .7s;
        }

        .lc-line-three {
            left: 28%;
            bottom: 30%;
            width: 155px;
            transform: rotate(-21deg);
            animation-delay: 1.2s;
        }

        .lc-line-four {
            right: 28%;
            bottom: 28%;
            width: 144px;
            transform: rotate(202deg);
            animation-delay: 1.7s;
        }

        .lc-message-chip {
            position: absolute;
            z-index: 5;
            border-radius: 8px;
            padding: 9px 12px;
            color: #134e4a;
            background: #fff8d7;
            font-size: 12px;
            font-weight: 900;
            box-shadow: 0 16px 30px rgba(15, 23, 42, .18);
            animation: lcSlideMessage 4s ease-in-out infinite;
        }

        .lc-message-chip.one {
            left: 18%;
            top: 44%;
        }

        .lc-message-chip.two {
            right: 15%;
            top: 61%;
            animation-delay: 1.8s;
        }

        @keyframes lcFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        @keyframes lcPulse {
            0%, 100% { opacity: .25; }
            45%, 65% { opacity: 1; }
        }

        @keyframes lcSlideMessage {
            0%, 100% { transform: translateY(8px); opacity: .55; }
            35%, 70% { transform: translateY(-4px); opacity: 1; }
        }

        @media (max-width: 575px) {
            .lc-conversation-visual {
                min-height: 340px;
            }

            .lc-orbit {
                inset: 76px 16px 24px;
            }

            .lc-inbox-core {
                width: 118px;
                height: 118px;
            }

            .lc-inbox-core img {
                width: 58px;
                height: 58px;
            }

            .lc-channel-node {
                width: 102px;
                min-height: 52px;
                font-size: 11px;
                padding: 10px;
            }

            .lc-message-chip {
                display: none;
            }
        }
    </style>
@endonce

<div class="lc-conversation-visual" aria-label="{{ $label }}">
    <div class="lc-visual-label">{{ $label }}</div>
    <div class="lc-orbit">
        <div class="lc-message-line lc-line-one"></div>
        <div class="lc-message-line lc-line-two"></div>
        <div class="lc-message-line lc-line-three"></div>
        <div class="lc-message-line lc-line-four"></div>

        <div class="lc-channel-node lc-node-instagram"><i>IG</i><span>Comments<br>and DMs</span></div>
        <div class="lc-channel-node lc-node-whatsapp"><i>WA</i><span>WhatsApp<br>sales</span></div>
        <div class="lc-channel-node lc-node-chat"><i>LC</i><span>Live chat<br>support</span></div>
        <div class="lc-channel-node lc-node-sales"><i>AI</i><span>Routing<br>and follow-up</span></div>

        <div class="lc-inbox-core">
            <div>
                <img src="{{ asset('leadochat-site/brand-mark.svg') }}" alt="">
                <span>One inbox</span>
            </div>
        </div>

        <div class="lc-message-chip one">New lead captured</div>
        <div class="lc-message-chip two">Agent reply sent</div>
    </div>
</div>
