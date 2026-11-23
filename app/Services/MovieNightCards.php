<?php

namespace App\Services;

use App\Models\MatchRoom;
use GdImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Renders Movie Night images with GD:
 *  - invite(): link preview (Open Graph, 1200×630) for room links shared in chats
 *  - match():  link preview once the room has matched
 *  - story():  portrait share card (1080×1350) for stories and group chats
 * Images are cached on the local disk per room state.
 */
class MovieNightCards
{
    private const FONT_BOLD = 'fonts/Figtree-800.ttf';

    private const FONT_REGULAR = 'fonts/Figtree-500.ttf';

    private const IMG = 'https://image.tmdb.org/t/p/';

    /** Returns the absolute path of a cached PNG. */
    public function invite(MatchRoom $room): string
    {
        $host = $room->participants()->where('is_host', true)->value('name') ?? 'A friend';
        $posters = array_slice(array_column($room->candidates ?? [], 'poster_path'), 0, 3);

        return $this->cached($room, 'invite', [$host, $posters, $room->is_persistent, $room->theme], function () use ($room, $host, $posters) {
            $img = $this->canvas(1200, 630);
            $this->glow($img, 980, 120, 520, [98, 195, 116], 38);

            // Poster fan on the right
            $slots = [[760, 120, 230], [880, 70, 260], [1000, 140, 220]];
            foreach (array_reverse(array_keys($slots)) as $i) {
                if (isset($posters[$i])) {
                    [$x, $y, $w] = $slots[$i];
                    $this->poster($img, $posters[$i], $x, $y, $w);
                }
            }
            // Posters fade into the text side (opaque at the left edge of the fan → clear)
            $this->fade($img, 700, 0, 260, 630, [10, 14, 21], horizontal: true, reverse: true);

            $this->brand($img, 64, 64);
            $theme = \App\Support\MovieNightThemes::get($room->theme);
            $kicker = $room->is_persistent ? 'US · SHARED WATCHLIST' : ($theme ? strtoupper($theme['name']) . ' · MOVIE NIGHT' : 'MOVIE NIGHT');
            $this->text($img, $kicker, 64, 205, 20, [138, 217, 154], true, 4);
            $headline = $room->is_persistent ? "{$host} wants to find movies you both love" : "{$host} invited you to movie night";
            $y = $this->paragraph($img, $headline, 64, 270, 54, 640, [255, 255, 255], true, 64);
            $this->paragraph($img, 'Swipe together. The first movie you all like wins.', 64, $y + 58, 24, 600, [190, 198, 210], false, 34);
            $this->pill($img, 'Room ' . $room->code, 64, 520, 26);

            return $img;
        });
    }

    public function match(MatchRoom $room, array $movie): string
    {
        $names = $this->names($room);

        return $this->cached($room, 'match', [$movie['id'], $names], function () use ($movie, $names) {
            $img = $this->canvas(1200, 630);
            if (!empty($movie['backdrop_path'])) {
                $this->cover($img, self::IMG . 'w1280' . $movie['backdrop_path'], 0, 0, 1200, 630);
                $this->shade($img, 0, 0, 1200, 630, 70);
            }
            $this->fade($img, 0, 0, 900, 630, [10, 14, 21], horizontal: true, reverse: true);

            $this->poster($img, $movie['poster_path'] ?? null, 64, 95, 290);
            $this->text($img, 'IT’S A MATCH!', 400, 150, 22, [138, 217, 154], true, 4);
            $y = $this->paragraph($img, $movie['title'], 400, 220, 52, 720, [255, 255, 255], true, 62, 3);
            $meta = trim(($movie['year'] ?? '') . (!empty($movie['rating']) ? '  ·  ' . number_format($movie['rating'], 1) . '/10' : ''));
            if ($meta) {
                $this->text($img, $meta, 400, $y + 50, 24, [200, 206, 216]);
            }
            $this->paragraph($img, $names ? "Picked by {$names}" : 'Picked on Movie Night', 400, $y + 96, 24, 720, [200, 206, 216], false, 32);
            $this->brand($img, 400, 520, 30);

            return $img;
        });
    }

    public function story(MatchRoom $room, array $movie): string
    {
        $names = $this->names($room);

        return $this->cached($room, 'story', [$movie['id'], $names], function () use ($room, $movie, $names) {
            $img = $this->canvas(1080, 1350);
            if (!empty($movie['backdrop_path'])) {
                $this->cover($img, self::IMG . 'w1280' . $movie['backdrop_path'], 0, 0, 1080, 760);
                $this->shade($img, 0, 0, 1080, 760, 60);
                $this->fade($img, 0, 360, 1080, 400, [10, 14, 21]);
            }
            $this->glow($img, 540, 600, 520, [98, 195, 116], 30);

            $this->poster($img, $movie['poster_path'] ?? null, 365, 120, 350); // ends at y≈645
            $this->centered($img, 'IT’S A MATCH!', 730, 30, [138, 217, 154], true, 6);
            $y = $this->paragraph($img, $movie['title'], 540, 820, 64, 920, [255, 255, 255], true, 76, 2, center: true);
            $meta = trim(($movie['year'] ?? '') . (!empty($movie['rating']) ? '  ·  ' . number_format($movie['rating'], 1) . '/10' : ''));
            if ($meta) {
                $this->centered($img, $meta, $y + 62, 30, [200, 206, 216]);
            }
            $count = $room->participants()->count();
            $saidYes = match (true) {
                !$names => 'Picked on Movie Night',
                $count === 1 => "{$names}’s pick for tonight",
                $count === 2 => "{$names} both said yes",
                default => "{$names} all said yes",
            };
            $this->paragraph($img, $saidYes, 540, $y + 128, 30, 900, [200, 206, 216], false, 40, 2, center: true);

            $this->brand($img, 405, 1230, 38);

            return $img;
        });
    }

    // ------------------------------------------------------------------ drawing helpers

    private function cached(MatchRoom $room, string $variant, array $state, callable $draw): string
    {
        $path = "movie-night-cards/{$room->code}-{$variant}-" . md5(json_encode($state)) . '.png';
        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            $img = $draw();
            ob_start();
            imagepng($img, null, 6);
            $disk->put($path, ob_get_clean());
            imagedestroy($img);
        }

        return $disk->path($path);
    }

    private function canvas(int $w, int $h): GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        // Note: no imageantialias() — GD ignores alpha when anti-aliasing lines, which would turn
        // every gradient into a solid band. TrueType text is anti-aliased regardless.
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 10, 14, 21));

        return $img;
    }

    private function color(GdImage $img, array $rgb, int $alpha = 0): int
    {
        return imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $alpha);
    }

    private function font(bool $bold): string
    {
        return resource_path($bold ? self::FONT_BOLD : self::FONT_REGULAR);
    }

    private function text(GdImage $img, string $text, int $x, int $y, int $size, array $rgb, bool $bold = false, int $tracking = 0): int
    {
        $font = $this->font($bold);
        if ($tracking === 0) {
            $box = imagettftext($img, $size, 0, $x, $y, $this->color($img, $rgb), $font, $text);

            return $box[2];
        }
        foreach (mb_str_split($text) as $char) { // letter-spacing for small caps labels
            $box = imagettftext($img, $size, 0, $x, $y, $this->color($img, $rgb), $font, $char);
            $x = $box[2] + $tracking;
        }

        return $x;
    }

    private function width(string $text, int $size, bool $bold, int $tracking = 0): int
    {
        $box = imagettfbbox($size, 0, $this->font($bold), $text);

        return ($box[2] - $box[0]) + $tracking * max(0, mb_strlen($text) - 1);
    }

    private function centered(GdImage $img, string $text, int $y, int $size, array $rgb, bool $bold = false, int $tracking = 0): void
    {
        $this->text($img, $text, (int) ((imagesx($img) - $this->width($text, $size, $bold, $tracking)) / 2), $y, $size, $rgb, $bold, $tracking);
    }

    /** Word-wrapped text; returns the baseline y of the last line. */
    private function paragraph(GdImage $img, string $text, int $x, int $y, int $size, int $maxWidth, array $rgb, bool $bold, int $lineHeight, int $maxLines = 4, bool $center = false): int
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = $line === '' ? $word : "{$line} {$word}";
            if ($line !== '' && $this->width($try, $size, $bold) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        $lines[] = $line;

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], '.,;: ') . '…';
        }

        foreach ($lines as $i => $l) {
            $lineY = $y + $i * $lineHeight;
            $center
                ? $this->centered($img, $l, $lineY, $size, $rgb, $bold)
                : $this->text($img, $l, $x, $lineY, $size, $rgb, $bold);
        }

        return $y + (count($lines) - 1) * $lineHeight;
    }

    private function remote(?string $url): ?GdImage
    {
        if (!$url) {
            return null;
        }
        try {
            $response = Http::timeout(6)->get($url);

            return $response->successful() ? (@imagecreatefromstring($response->body()) ?: null) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Draws a remote image cropped to fill the box (like CSS object-fit: cover). */
    private function cover(GdImage $img, string $url, int $x, int $y, int $w, int $h): void
    {
        $src = $this->remote($url);
        if (!$src) {
            return;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = max($w / $sw, $h / $sh);
        $cw = (int) ($w / $scale);
        $ch = (int) ($h / $scale);
        imagecopyresampled($img, $src, $x, $y, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $w, $h, $cw, $ch);
        imagedestroy($src);
    }

    /** Poster with a soft drop shadow and a thin light border. */
    private function poster(GdImage $img, ?string $path, int $x, int $y, int $w): void
    {
        $h = (int) round($w * 1.5);
        for ($i = 18; $i > 0; $i -= 3) {
            imagefilledrectangle($img, $x - $i + 10, $y - $i + 16, $x + $w + $i - 10, $y + $h + $i + 4, $this->color($img, [0, 0, 0], 118));
        }
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $this->color($img, [26, 34, 48]));
        if ($path) {
            $this->cover($img, self::IMG . 'w342' . $path, $x, $y, $w, $h);
        }
        imagerectangle($img, $x, $y, $x + $w, $y + $h, $this->color($img, [255, 255, 255], 100));
    }

    private function shade(GdImage $img, int $x, int $y, int $w, int $h, int $alpha): void
    {
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $this->color($img, [10, 14, 21], $alpha));
    }

    /** Gradient from transparent to the given colour (vertical by default). */
    private function fade(GdImage $img, int $x, int $y, int $w, int $h, array $rgb, bool $horizontal = false, bool $reverse = false): void
    {
        $steps = $horizontal ? $w : $h;
        for ($i = 0; $i < $steps; $i++) {
            $t = $i / max(1, $steps - 1);
            $alpha = (int) round(127 * ($reverse ? $t : 1 - $t));
            $c = $this->color($img, $rgb, $alpha);
            $horizontal
                ? imageline($img, $x + $i, $y, $x + $i, $y + $h, $c)
                : imageline($img, $x, $y + $i, $x + $w, $y + $i, $c);
        }
    }

    private function glow(GdImage $img, int $cx, int $cy, int $radius, array $rgb, int $strength): void
    {
        for ($r = $radius; $r > 0; $r -= 8) {
            $alpha = 127 - (int) round($strength * (1 - $r / $radius) / 6);
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $this->color($img, $rgb, max(0, min(127, $alpha))));
        }
    }

    private function pill(GdImage $img, string $label, int $x, int $y, int $size): void
    {
        $w = $this->width($label, $size, true) + 48;
        $h = $size + 34;
        imagefilledrectangle($img, $x + $h / 2, $y, $x + $w - $h / 2, $y + $h, $this->color($img, [47, 132, 66]));
        imagefilledellipse($img, (int) ($x + $h / 2), (int) ($y + $h / 2), $h, $h, $this->color($img, [47, 132, 66]));
        imagefilledellipse($img, (int) ($x + $w - $h / 2), (int) ($y + $h / 2), $h, $h, $this->color($img, [47, 132, 66]));
        $this->text($img, $label, $x + 24, $y + $size + 15, $size, [255, 255, 255], true);
    }

    /** Eye-with-play mark + "envydeo" wordmark. */
    private function brand(GdImage $img, int $x, int $y, int $size = 30): void
    {
        $green = $this->color($img, [76, 175, 98]);
        $s = $size / 30;
        imagesetthickness($img, max(2, (int) round(4 * $s)));
        $w = (int) (52 * $s);
        $h = (int) (34 * $s);
        imagearc($img, $x + $w / 2, (int) ($y - $h / 2 + 18 * $s), (int) ($w * 1.05), (int) ($h * 1.35), 200, 340, $green);
        imagearc($img, $x + $w / 2, (int) ($y - $h / 2 - 2 * $s), (int) ($w * 1.05), (int) ($h * 1.35), 20, 160, $green);
        imagesetthickness($img, 1);
        $cx = $x + $w / 2 + 2 * $s;
        $cy = $y - $h / 2 + 8 * $s;
        imagefilledpolygon($img, [
            (int) ($cx - 7 * $s), (int) ($cy - 10 * $s),
            (int) ($cx - 7 * $s), (int) ($cy + 10 * $s),
            (int) ($cx + 10 * $s), (int) $cy,
        ], $green);

        $tx = $x + $w + (int) (14 * $s);
        $end = $this->text($img, 'envy', $tx, (int) ($y + 4 * $s), $size, [255, 255, 255], true);
        $this->text($img, 'deo', $end, (int) ($y + 4 * $s), $size, [138, 217, 154], true);
    }

    private function names(MatchRoom $room): string
    {
        $names = $room->participants()->orderBy('id')->pluck('name')->map(fn ($n) => trim($n))->filter()->values();

        return match (true) {
            $names->isEmpty() => '',
            $names->count() === 1 => $names[0],
            $names->count() <= 3 => $names->slice(0, -1)->implode(', ') . ' & ' . $names->last(),
            default => $names->take(2)->implode(', ') . ' & ' . ($names->count() - 2) . ' friends',
        };
    }
}
