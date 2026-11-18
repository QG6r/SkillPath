<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = ['module_id', 'title', 'content', 'video_url', 'order'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Turns a YouTube or Vimeo page link into an address that can be shown in an iframe.
     * Returns null for any other link (the page then shows it as a plain link).
     */
    public static function toEmbedUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $youtube = [
            '~^https?://(?:www\.|m\.)?youtube\.com/watch\?(?:[^#]*&)?v=([A-Za-z0-9_-]{11})~',
            '~^https?://youtu\.be/([A-Za-z0-9_-]{11})~',
            '~^https?://(?:www\.)?youtube\.com/embed/([A-Za-z0-9_-]{11})~',
        ];

        foreach ($youtube as $pattern) {
            if (preg_match($pattern, $url, $match)) {
                return 'https://www.youtube.com/embed/'.$match[1];
            }
        }

        if (preg_match('~^https?://(?:www\.)?vimeo\.com/(\d+)~', $url, $match)) {
            return 'https://player.vimeo.com/video/'.$match[1];
        }

        return null;
    }
}
