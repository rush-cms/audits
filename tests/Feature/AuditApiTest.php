<?php

declare(strict_types=1);

use App\Jobs\GenerateAuditPdfJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('queues audit job when receiving valid pagespeed payload', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $fixturePath = dirname(__DIR__).'/Fixtures/pagespeed_mock.json';
    $payload = json_decode(file_get_contents($fixturePath), true);

    $response = $this->withToken($token)
        ->postJson('/api/v1/scan', $payload);

    $response->assertStatus(202)
        ->assertJsonStructure(['message', 'audit_id']);

    Queue::assertPushed(GenerateAuditPdfJob::class);
});

it('returns 401 without authentication', function (): void {
    $response = $this->postJson('/api/v1/scan', ['test' => 'data']);

    $response->assertStatus(401);
});

it('handles array-wrapped payload from n8n', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $fixturePath = dirname(__DIR__).'/Fixtures/pagespeed_mock.json';
    $payload = json_decode(file_get_contents($fixturePath), true);

    $response = $this->withToken($token)
        ->postJson('/api/v1/scan', $payload);

    $response->assertStatus(202);

    Queue::assertPushed(GenerateAuditPdfJob::class, function ($job) {
        return $job->auditData->score->toPercentage() === 69;
    });
});
