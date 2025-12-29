<?php

declare(strict_types=1);

use App\Models\Audit;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

it('logs API requests to audit channel', function () {
    Log::fake();

    $token = Sanctum::actingAs(\App\Models\User::factory()->create());

    postJson('/api/v1/scan', [
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
    ]);

    Log::channel('audits')->assertLogged('info', function ($message, $context) {
        return $message === 'API request received' &&
            isset($context['endpoint']) &&
            isset($context['token_id']) &&
            isset($context['ip']);
    });
});

it('logs new audit creation', function () {
    Log::fake();

    $token = Sanctum::actingAs(\App\Models\User::factory()->create());

    postJson('/api/v1/scan', [
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
    ]);

    Log::channel('audits')->assertLogged('info', function ($message, $context) {
        return $message === 'New audit created' &&
            isset($context['audit_id']) &&
            isset($context['url']);
    });
});

it('logs validation errors', function () {
    Log::fake();

    $token = Sanctum::actingAs(\App\Models\User::factory()->create());

    postJson('/api/v1/scan', [
        'url' => 'invalid-url',
    ]);

    Log::channel('audits')->assertLogged('warning', function ($message, $context) {
        return $message === 'Validation failed' &&
            isset($context['error']);
    });
});

it('records audit trail on creation', function () {
    $token = Sanctum::actingAs(\App\Models\User::factory()->create());

    $response = postJson('/api/v1/scan', [
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
    ]);

    $auditId = $response->json('audit_id');
    $audit = Audit::find($auditId);

    expect($audit->created_by_token_id)->toBe($token->id);
    expect($audit->created_by_ip)->not->toBeNull();
    expect($audit->user_agent)->not->toBeNull();
});

it('stores error context on failure', function () {
    $audit = Audit::factory()->create();

    $audit->markAsFailed('Test error', [
        'exception' => 'TestException',
        'file' => 'test.php',
        'line' => 123,
    ]);

    expect($audit->fresh()->error_context)->toHaveKey('exception');
    expect($audit->fresh()->error_context)->toHaveKey('file');
    expect($audit->fresh()->error_context)->toHaveKey('line');
});
