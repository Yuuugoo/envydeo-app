<?php

namespace App\Http\Controllers;

use App\Services\Tmdb;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function results(Request $request, Tmdb $tmdb)
{
    $query = $request->input('query');
    $mediaType = $request->input('media_type', 'all');
    $sortBy = $request->input('sort_by', 'popularity');
    $page = $request->input('page', 1);

    if (empty($query)) {
        return redirect()->route('movie.index');
    }

    $page = max(1, min(500, (int) $page));
    $tvResults = [];
    $movieResults = [];

    // Run the TV and movie searches in parallel (cached for 10 minutes).
    $searchParams = ['query' => $query, 'include_adult' => 'false', 'page' => $page];
    $requests = [];
    if ($mediaType === 'all' || $mediaType === 'tv') {
        $requests['tv'] = ['/search/tv', $searchParams];
    }
    if ($mediaType === 'all' || $mediaType === 'movie') {
        $requests['movie'] = ['/search/movie', $searchParams];
    }
    $responses = $tmdb->many($requests, Tmdb::MINUTES_10);

    if (isset($responses['tv'])) {
        if (isset($responses['tv']['results'])) {
            $tvData = $responses['tv'];
            $tvResults = $tvData['results'];
            $tvTotalPages = $tvData['total_pages'] ?? 1;
            $tvTotalResults = $tvData['total_results'] ?? 0;
            
            $tvResults = array_map(function ($item) {
                return array_merge($item, [
                    'media_type' => 'tv',
                    'sort_date' => $item['first_air_date'] ?? null,
                    'sort_title' => $item['name'] ?? '',
                ]);
            }, $tvResults);
        }
    }

    if (isset($responses['movie'])) {
        if (isset($responses['movie']['results'])) {
            $movieData = $responses['movie'];
            $movieResults = $movieData['results'];
            $movieTotalPages = $movieData['total_pages'] ?? 1;
            $movieTotalResults = $movieData['total_results'] ?? 0;
            
            $movieResults = array_map(function ($item) {
                return array_merge($item, [
                    'media_type' => 'movie',
                    'sort_date' => $item['release_date'] ?? null,
                    'sort_title' => $item['title'] ?? '',
                ]);
            }, $movieResults);
        }
    }

    $searchResults = collect(array_merge($tvResults, $movieResults));

    switch ($sortBy) {
        case 'release_date':
            $searchResults = $searchResults->sortByDesc('sort_date');
            break;
        case 'title':
            $searchResults = $searchResults->sortBy('sort_title');
            break;
        case 'vote_average':
            $searchResults = $searchResults->sortByDesc('vote_average');
            break;
        default:
            $searchResults = $searchResults->sortByDesc('popularity');
            break;
    }

    $totalPages = max($movieTotalPages ?? 1, $tvTotalPages ?? 1);

    $searchResultsWithPosters = $searchResults->filter(function($result) {
        return !empty($result['poster_path']);
    });

    $displayableResults = 0;
    if ($mediaType === 'all') {
        $displayableResults = $searchResultsWithPosters->count();
    } elseif ($mediaType === 'tv') {
        $displayableResults = collect($tvResults)->filter(function($result) {
            return !empty($result['poster_path']);
        })->count();

        if (isset($tvTotalResults) && $tvResults) {
            $posterRatio = count($tvResults) > 0 ? $displayableResults / count($tvResults) : 0;
            $displayableResults = ceil($tvTotalResults * $posterRatio);
        }
    } else {
        $displayableResults = collect($movieResults)->filter(function($result) {
            return !empty($result['poster_path']);
        })->count();

        if (isset($movieTotalResults) && $movieResults) {
            $posterRatio = count($movieResults) > 0 ? $displayableResults / count($movieResults) : 0;
            $displayableResults = ceil($movieTotalResults * $posterRatio);
        }
    }

    $totalResults = 0;
    if ($mediaType === 'all') {
        $totalResults = ($tvTotalResults ?? 0) + ($movieTotalResults ?? 0);
    } elseif ($mediaType === 'tv') {
        $totalResults = $tvTotalResults ?? 0;
    } else {
        $totalResults = $movieTotalResults ?? 0;
    }

    return view('results', [
        'searchResults' => $searchResults->values(),
        'query' => $query,
        'selectedMediaType' => $mediaType,
        'selectedSortBy' => $sortBy,
        'currentPage' => (int)$page,
        'totalPages' => $totalPages,
        'totalResults' => $totalResults,
        'displayableResults' => $displayableResults,
    ]);
}
}