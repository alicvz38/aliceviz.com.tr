<?php

namespace App\Models;

use App\Core\Model;

/** Canlı destek mesajı (sender: visitor | admin | system) */
class ChatMessage extends Model
{
    protected static string $table = 'chat_messages';
    protected static array $fillable = ['conversation_id', 'sender', 'body', 'created_at'];

    /** @return array[] Belirtilen kimlikten sonraki mesajlar (istemciye gönderilecek biçimde) */
    public static function after(int $conversationId, int $afterId = 0, int $limit = 200): array
    {
        return array_map(
            static fn (self $m) => $m->toClient(),
            static::where('conversation_id = ? AND id > ?', [$conversationId, $afterId], 'id ASC', $limit)
        );
    }

    public function toClient(): array
    {
        return [
            'id'      => (int) $this->id,
            'conv'    => (int) $this->conversation_id,
            'sender'  => (string) $this->sender,
            'body'    => (string) $this->body,
            'time'    => format_date($this->created_at, 'H:i'),
            'date'    => format_date($this->created_at, 'd.m.Y'),
        ];
    }
}
