\App\Models\Conversation::query()->each(function ($conversation) {
    $unreadCount = \App\Models\Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('direction', 'inbound')
        ->whereNull('read_at')
        ->count();

    $conversation->update([
        'unread_count' => $unreadCount,
    ]);

    echo 'conversation_' . $conversation->id . '_unread_' . $unreadCount . PHP_EOL;
});
