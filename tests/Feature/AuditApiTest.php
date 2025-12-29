<?php

declare(strict_types=1);

use App\Jobs\FetchPageSpeedJob;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates audit and queues job when receiving valid URL', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'https://example.com',
            'strategy' => 'mobile',
            'lang' => 'en',
        ]);

    $response->assertStatus(202)
        ->assertJsonStructure(['message', 'audit_id', 'url', 'strategy', 'lang', 'status'])
        ->assertJson([
            'strategy' => 'mobile',
            'lang' => 'en',
            'status' => 'pending',
        ]);

    expect(Audit::count())->toBe(1);

    $audit = Audit::first();
    expect($audit->url)->toBe('https://example.com')
        ->and($audit->strategy)->toBe('mobile')
        ->and($audit->lang)->toBe('en')
        ->and($audit->status)->toBe('pending');

    Queue::assertPushed(FetchPageSpeedJob::class, function ($job) use ($audit) {
        return $job->auditId === $audit->id
            && $job->url === 'https://example.com'
            && $job->strategy === 'mobile'
            && $job->lang === 'en';
    });
});

it('returns 401 without authentication', function (): void {
    $response = $this->postJson('/api/v1/scan', [
        'url' => 'https://example.com',
    ]);

    $response->assertStatus(401);
});

it('validates URL format', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'invalid-url',
        ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'Invalid request']);
});

it('uses mobile as default strategy', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'https://example.com',
        ]);

    $response->assertStatus(202)
        ->assertJson(['strategy' => 'mobile']);
});

it('uses en as default language', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'https://example.com',
        ]);

    $response->assertStatus(202)
        ->assertJson(['lang' => 'en']);
});

it('implements idempotency within time window', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response1 = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'https://example.com',
            'strategy' => 'mobile',
            'lang' => 'en',
        ]);

    $auditId1 = $response1->json('audit_id');

    $response2 = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'https://example.com',
            'strategy' => 'mobile',
            'lang' => 'en',
        ]);

    $auditId2 = $response2->json('audit_id');

    expect($auditId1)->toBe($auditId2);
    expect(Audit::count())->toBe(1);

    Queue::assertPushed(FetchPageSpeedJob::class, 1);
});

it('creates different audits for different strategies', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response1 = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'https://example.com',
            'strategy' => 'mobile',
        ]);

    $auditId1 = $response1->json('audit_id');

    $response2 = $this->withToken($token)
        ->postJson('/api/v1/scan', [
            'url' => 'https://example.com',
            'strategy' => 'desktop',
        ]);

    $auditId2 = $response2->json('audit_id');

    expect($auditId1)->not->toBe($auditId2);
    expect(Audit::count())->toBe(2);

    Queue::assertPushed(FetchPageSpeedJob::class, 2);
});

it('retrieves audit by id', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 85,
        'metrics' => [
            'lcp' => '1.2 s',
            'fcp' => '0.8 s',
            'cls' => '0.05',
        ],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/audits/{$audit->id}");

    $response->assertSuccessful()
        ->assertJson([
            'id' => $audit->id,
            'url' => 'https://example.com',
            'strategy' => 'mobile',
            'lang' => 'en',
            'status' => 'completed',
            'score' => 85,
        ])
        ->assertJsonStructure([
            'id',
            'url',
            'strategy',
            'lang',
            'status',
            'score',
            'metrics',
            'pdf_url',
            'error_message',
            'created_at',
            'completed_at',
        ]);
});
