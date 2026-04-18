\App\Models\Message::query()
    ->where('conversation_id', 1)
    ->where('direction', 'inbound')
    ->update([
        'read_at' => null,
    ]);

\App\Models\Message::query()
    ->where('conversation_id', 2)
    ->where('direction', 'inbound')
    ->update([
        'read_at' => null,
    ]);

\App\Models\Conversation::query()->each(function ($conversation) {
    $unreadCount = \App\Models\Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('direction', 'inbound')
        ->whereNull('read_at')
        ->count();

    $conversation->update([
        'unread_count' => $unreadCount,
    ]);

    echo 'conversation_' . $conversation->id . '_reset_unread_' . $unreadCount . PHP_EOL;
});
