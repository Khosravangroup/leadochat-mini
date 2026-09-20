@once
    <style>
        .lc-pricing-visual {
            position: relative;
            min-height: 380px;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fbfa;
            border: 1px solid rgba(255, 255, 255, .28);
            box-shadow: 0 28px 80px rgba(15, 23, 42, .2);
        }

        .lc-pricing-visual::before {
            content: "";
            position: absolute;
            inset: 18px;
            border: 1px solid #d9e7e3;
            border-radius: 8px;
        }

        .lc-pricing-plan {
            position: absolute;
            bottom: 46px;
            width: 124px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #d9e7e3;
            padding: 16px;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .1);
            animation: lcPlanLift 5s ease-in-out infinite;
        }

        .lc-pricing-plan strong {
            display: block;
            color: #134e4a;
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .lc-pricing-plan span {
            display: block;
            height: 9px;
            border-radius: 999px;
            background: #8aa39d;
            margin-top: 9px;
        }

        .lc-pricing-plan.one {
            left: 48px;
            height: 160px;
        }

        .lc-pricing-plan.two {
            left: 50%;
            height: 218px;
            transform: translateX(-50%);
            background: #fff8d7;
            border-color: #f6c55b;
            animation-delay: .7s;
        }

        .lc-pricing-plan.three {
            right: 48px;
            height: 190px;
            animation-delay: 1.4s;
        }

        .lc-pricing-plan.two::before {
            content: "POPULAR";
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            border-radius: 8px;
            padding: 0 8px;
            background: #0f766e;
            color: #fff8d7;
            font-size: 10px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .lc-price-meter {
            position: absolute;
            left: 48px;
            right: 48px;
            top: 46px;
            z-index: 3;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #d9e7e3;
            padding: 16px;
        }

        .lc-price-meter strong {
            color: #134e4a;
            font-size: 18px;
            font-weight: 900;
        }

        .lc-price-line {
            position: relative;
            height: 10px;
            margin-top: 14px;
            border-radius: 999px;
            background: #d9e7e3;
            overflow: hidden;
        }

        .lc-price-line::before {
            content: "";
            position: absolute;
            inset: 0 38% 0 0;
            border-radius: inherit;
            background: linear-gradient(90deg, #ffd84d, #f97316);
            animation: lcPriceMeter 4s ease-in-out infinite;
        }

        @keyframes lcPlanLift {
            0%, 100% { translate: 0 0; }
            50% { translate: 0 -8px; }
        }

        @keyframes lcPriceMeter {
            0%, 100% { right: 52%; }
            50% { right: 18%; }
        }

        @media (prefers-reduced-motion: reduce) {
            .lc-pricing-plan,
            .lc-price-line::before {
                animation: none;
            }
        }

        @media (max-width: 575px) {
            .lc-pricing-visual {
                min-height: 470px;
            }

            .lc-pricing-plan {
                width: 104px;
                bottom: 40px;
            }

            .lc-pricing-plan.one { left: 22px; }
            .lc-pricing-plan.three { right: 22px; }
        }
    </style>
@endonce

<div class="lc-pricing-visual" aria-label="Leadochat pricing scale">
    <div class="lc-price-meter">
        <strong>Scale when the workflow is ready</strong>
        <div class="lc-price-line"></div>
    </div>
    <div class="lc-pricing-plan one"><strong>Starter</strong><span></span><span style="width:72%"></span><span style="width:56%"></span></div>
    <div class="lc-pricing-plan two"><strong>Growth</strong><span></span><span style="width:86%"></span><span style="width:66%"></span></div>
    <div class="lc-pricing-plan three"><strong>Enterprise</strong><span></span><span style="width:78%"></span><span style="width:60%"></span></div>
</div>
