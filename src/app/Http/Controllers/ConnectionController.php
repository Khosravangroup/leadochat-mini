<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        $connections = $workspace
            ? $workspace->providerConnections()->latest()->get()
            : collect();

        $providerCards = [
            [
                'provider' => 'instagram',
                'title' => 'Instagram',
                'subtitle' => 'Instagram Login, messaging, comments, publishing, stories, ads base',
                'account_type' => 'instagram_account',
            ],
            [
                'provider' => 'facebook',
                'title' => 'Facebook Page + Messenger',
                'subtitle' => 'Facebook Page connection, Messenger inbox, posts, comments',
                'account_type' => 'facebook_page',
            ],
            [
                'provider' => 'whatsapp',
                'title' => 'WhatsApp Business',
                'subtitle' => 'Embedded Signup, messaging, media, templates, webhooks',
                'account_type' => 'whatsapp_business_account',
            ],
        ];

        return view('connections.index', [
            'workspace' => $workspace,
            'connections' => $connections,
            'providerCards' => $providerCards,
        ]);
    }
}
