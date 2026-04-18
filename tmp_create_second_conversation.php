$workspace = \App\Models\Workspace::find(1);

if (!$workspace) {
    echo "workspace_missing\n";
    return;
}

$providerConnection = \App\Models\ProviderConnection::where('workspace_id', 1)
    ->where('provider', 'instagram')
    ->first();

if (!$providerConnection) {
    echo "provider_connection_missing\n";
    return;
}

$existing = \App\Models\Conversation::where('provider_conversation_id', 'ig-conv-1002')->first();

if ($existing) {
    echo "conversation_already_exists_id_" . $existing->id . "\n";
    return;
}

$conversation = \App\Models\Conversation::create([
    'workspace_id' => $workspace->id,
    'provider_connection_id' => $providerConnection->id,
    'provider' => 'instagram',
    'provider_conversation_id' => 'ig-conv-1002',
    'type' => 'direct',
    'title' => 'Michael Brown',
    'avatar_url' => 'https://i.pravatar.cc/150?img=18',
    'status' => 'active',
    'last_message_preview' => 'Thanks, I will check and get back to you.',
    'unread_count' => 1,
    'is_archived' => false,
    'is_muted' => false,
    'last_message_at' => now()->subMinute(),
    'meta' => ['platform' => 'instagram'],
]);

$self = \App\Models\ConversationParticipant::create([
    'conversation_id' => $conversation->id,
    'provider_user_id' => 'self-instagram-business',
    'display_name' => 'Leadochat Mini',
    'handle' => '@leadochatmini',
    'avatar_url' => 'https://i.pravatar.cc/150?img=12',
    'role' => 'owner',
    'is_self' => true,
    'meta' => [],
]);

$customer = \App\Models\ConversationParticipant::create([
    'conversation_id' => $conversation->id,
    'provider_user_id' => 'ig-user-777',
    'display_name' => 'Michael Brown',
    'handle' => '@michael.b',
    'avatar_url' => 'https://i.pravatar.cc/150?img=18',
    'role' => 'participant',
    'is_self' => false,
    'meta' => [],
]);

\App\Models\Message::create([
    'conversation_id' => $conversation->id,
    'sender_participant_id' => $customer->id,
    'reply_to_message_id' => null,
    'provider' => 'instagram',
    'provider_message_id' => 'ig-msg-100',
    'provider_reply_to_message_id' => null,
    'direction' => 'inbound',
    'message_type' => 'text',
    'text_body' => 'Hello, is this product available in blue color?',
    'caption' => null,
    'status' => 'received',
    'sent_at' => now()->subMinutes(9),
    'received_at' => now()->subMinutes(9),
    'read_at' => null,
    'failed_at' => null,
    'last_error' => null,
    'meta' => [],
]);

\App\Models\Message::create([
    'conversation_id' => $conversation->id,
    'sender_participant_id' => $self->id,
    'reply_to_message_id' => null,
    'provider' => 'instagram',
    'provider_message_id' => 'ig-msg-101',
    'provider_reply_to_message_id' => null,
    'direction' => 'outbound',
    'message_type' => 'text',
    'text_body' => 'Yes, blue is available.',
    'caption' => null,
    'status' => 'read',
    'sent_at' => now()->subMinutes(7),
    'received_at' => now()->subMinutes(7),
    'read_at' => now()->subMinutes(5),
    'failed_at' => null,
    'last_error' => null,
    'meta' => [],
]);

\App\Models\Message::create([
    'conversation_id' => $conversation->id,
    'sender_participant_id' => $customer->id,
    'reply_to_message_id' => null,
    'provider' => 'instagram',
    'provider_message_id' => 'ig-msg-102',
    'provider_reply_to_message_id' => null,
    'direction' => 'inbound',
    'message_type' => 'text',
    'text_body' => 'Thanks, I will check and get back to you.',
    'caption' => null,
    'status' => 'received',
    'sent_at' => now()->subMinutes(2),
    'received_at' => now()->subMinutes(2),
    'read_at' => null,
    'failed_at' => null,
    'last_error' => null,
    'meta' => [],
]);

echo "created_conversation_id_" . $conversation->id . "\n";
