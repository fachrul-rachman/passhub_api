<?php

namespace App\Services;

class PasswordGeneratorService
{
    public function generate(
        int $length = 12,
        int $uppercaseCount = 1,
        int $numberCount = 1,
        int $symbolCount = 1
    ): string {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $password = '';

        for ($i=0; $i < $uppercaseCount ; $i++) { 
            $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        }

        for ($i=0; $i < $numberCount ; $i++) { 
            $password .= $numbers[rand(0, strlen($numbers) - 1)];
        }

        for ($i=0; $i < $symbolCount ; $i++) { 
            $password .= $symbols[rand(0, strlen($symbols) - 1)];
        }

        $reminingLength = $length - strlen($password);
        for ($i=0; $i < $reminingLength ; $i++) { 
            $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        }

        return str_shuffle($password);
    }
}