<?php

namespace App\Support;

/** Menerjemahkan tautan video. Hanya https; YouTube dipasang sebagai embed privasi-ketat. */
final class VideoLink
{
    /** @return array{provider:string, ref:string} */
    public static function parse(string $url): array
    {
        $id = self::youtubeId($url);

        return $id ? ['provider' => 'youtube', 'ref' => $id] : ['provider' => 'link', 'ref' => $url];
    }

    public static function youtubeId(string $url): ?string
    {
        $host = strtolower((string) preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $id = null;

        if ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $id = $query['v'] ?? null;
            if (! $id && preg_match('#^/(embed|shorts|live)/([A-Za-z0-9_-]{11})#', $path, $m)) {
                $id = $m[2];
            }
        }

        return $id && preg_match('/^[A-Za-z0-9_-]{11}$/', (string) $id) ? $id : null;
    }

    public static function embedUrl(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$id;
    }
}
