<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class CreateTokenCommand extends Command
{
    protected $signature = 'audit:create-token {name : The name of the token/client}';

    protected $description = 'Create a new API token for a client';

    public function handle(): int
    {
        $name = $this->argument('name');

        $user = User::firstOrCreate(
            ['email' => 'api@audits.local'],
            [
                'name' => 'API Service Account',
                'password' => bcrypt(bin2hex(random_bytes(16))),
            ]
        );

        $token = $user->createToken($name);

        $this->info('Token created successfully:');
        $this->newLine();
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->warn('Save this token now. It will not be shown again.');

        return self::SUCCESS;
    }
}
