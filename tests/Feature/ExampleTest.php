<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        Http::fake(['api.themoviedb.org/*' => Http::response(['results' => [], 'genres' => []])]);

        $this->get('/')->assertStatus(200);
    }
}
