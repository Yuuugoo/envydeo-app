<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Works out which country's "Where to watch" to show.
 *
 * Order: the country the viewer picked (cookie) → Cloudflare's CF-IPCountry header
 * (only when the request came through a trusted proxy) → a local GeoIP lookup of the
 * viewer's IP (DB-IP Lite, offline: the IP never leaves the server and is not stored)
 * → the browser language (en-PH → PH) → US.
 */
class ViewerRegion
{
    public const COOKIE = 'region';

    public const GEOIP_PATH = 'geoip/dbip-country-lite.mmdb'; // under storage/app

    private ?Reader $reader = null;

    private bool $readerTried = false;

    public function __construct(private Tmdb $tmdb) {}

    /** ISO code => English name for every country TMDB has watch-provider data for. */
    public function available(): array
    {
        return Cache::remember('tmdb:watch-regions', Tmdb::WEEK, function () {
            $list = collect($this->tmdb->get('/watch/providers/regions', [], Tmdb::WEEK)['results'] ?? [])
                ->mapWithKeys(fn ($r) => [strtoupper($r['iso_3166_1'] ?? '') => $r['english_name'] ?? ''])
                ->filter(fn ($name, $code) => strlen($code) === 2 && $name !== '')
                ->sort()
                ->all();

            return $list ?: MovieNightDealer::REGIONS;
        });
    }

    public function name(string $code): string
    {
        return $this->available()[$code] ?? MovieNightDealer::REGIONS[$code] ?? $code;
    }

    public function resolve(Request $request): string
    {
        $available = $this->available();
        $valid = fn (?string $code) => $code && isset($available[$code = strtoupper($code)]) ? $code : null;

        return $valid($request->cookie(self::COOKIE))
            ?? ($request->isFromTrustedProxy() ? $valid($request->header('CF-IPCountry')) : null)
            ?? $valid($this->countryForIp($request->ip()))
            ?? $valid($this->countryFromLocale($request->getPreferredLanguage()))
            ?? 'US';
    }

    /** Like resolve(), limited to the Movie Night region list (discover filters). */
    public function resolveForMovieNight(Request $request): string
    {
        $code = $this->resolve($request);

        return isset(MovieNightDealer::REGIONS[$code]) ? $code : 'US';
    }

    public function countryForIp(?string $ip): ?string
    {
        // Private/reserved addresses (localhost, LAN) have no country.
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }
        try {
            return $this->reader()?->get($ip)['country']['iso_code'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    private function countryFromLocale(?string $locale): ?string
    {
        return preg_match('/^[a-z]{2,3}[_-]([a-z]{2})$/i', (string) $locale, $m) ? strtoupper($m[1]) : null;
    }

    private function reader(): ?Reader
    {
        if (!$this->readerTried) {
            $this->readerTried = true;
            $path = storage_path('app/' . self::GEOIP_PATH);
            try {
                $this->reader = is_file($path) ? new Reader($path) : null;
            } catch (Throwable) {
                $this->reader = null;
            }
        }

        return $this->reader;
    }
}
