<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class JWTSecretKeyGenerator extends Command
{
    protected $signature = 'jwt:key';

    protected $description = 'generate json web token secret key';

    public function handle() {
        $key = $this->generateKey();
        if (Str::contains(file_get_contents($this->envPath()), 'JWT_SECRET_KEY') === false) {
            #append content in .env file
            if( file_put_contents($this->envPath(), PHP_EOL . "JWT_SECRET_KEY=$key", FILE_APPEND) > 0 ) {
                $this->info("JWT key [$key] set successfully.");
            }
        }
    }

    protected function envPath() {
        return base_path('.env');
    }

    protected function generateKey() {
        return Str::random(32);
    }
}