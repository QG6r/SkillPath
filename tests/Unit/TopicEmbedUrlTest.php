<?php

namespace Tests\Unit;

use App\Models\Topic;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TopicEmbedUrlTest extends TestCase
{
    public static function links(): array
    {
        return [
            'youtube watch link' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'youtube link with other parameters first' => ['https://www.youtube.com/watch?list=PL1&v=dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'youtube short link' => ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'youtube embed link' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'vimeo link' => ['https://vimeo.com/123456789', 'https://player.vimeo.com/video/123456789'],
            'another site' => ['https://example.com/video.mp4', null],
            'a script address' => ['javascript:alert(1)', null],
            'a lookalike host' => ['https://evil.com/?u=https://www.youtube.com/watch?v=dQw4w9WgXcQ', null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('links')]
    public function test_a_video_link_becomes_an_embed_address(string $link, ?string $expected): void
    {
        $this->assertSame($expected, Topic::toEmbedUrl($link));
    }

    public function test_a_missing_link_gives_nothing(): void
    {
        $this->assertNull(Topic::toEmbedUrl(null));
    }
}
