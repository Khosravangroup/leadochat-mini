<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'instagram' => [
        'client_id' => env('INSTAGRAM_CLIENT_ID'),
        'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
        'app_secret' => env('INSTAGRAM_APP_SECRET', env('INSTAGRAM_CLIENT_SECRET')),
        'webhook_app_id' => env('INSTAGRAM_WEBHOOK_APP_ID', env('META_APP_ID', env('INSTAGRAM_CLIENT_ID'))),
        'webhook_app_secret' => env('INSTAGRAM_WEBHOOK_APP_SECRET', env('META_APP_SECRET', env('INSTAGRAM_APP_SECRET', env('INSTAGRAM_CLIENT_SECRET')))),
        'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),
        'graph_version' => env('INSTAGRAM_GRAPH_VERSION', 'v25.0'),
        'scopes' => env('INSTAGRAM_SCOPES', 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish,instagram_business_manage_insights'),
        'webhook_subscribed_fields' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('INSTAGRAM_WEBHOOK_SUBSCRIBED_FIELDS', 'messages,comments,standby,messaging_postbacks,message_reactions,messaging_seen,messaging_referral,messaging_handover,message_edit'))
        ))),
        'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', 'leadochat-mini-instagram-verify-token'),
    ],

    'meta' => [
        'graph_version' => env('META_GRAPH_VERSION', env('INSTAGRAM_GRAPH_VERSION', 'v25.0')),
        'commerce_review_scopes' => env('META_COMMERCE_REVIEW_SCOPES', 'business_management,catalog_management,ads_read,ads_management'),
    ],

];
