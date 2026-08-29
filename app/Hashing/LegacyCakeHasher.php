<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class LegacyCakeHasher implements HasherContract
{
    protected string $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgsdsfdsfdaC9mi';

    public function info($hashedValue): array
    {
        return ['alg' => 'sha1', 'salt' => $this->salt];
    }

    public function make($value, array $options = []): string
    {
        return sha1($this->salt . $value);
    }

    public function check($value, $hashedValue, array $options = []): bool
    {
        if (empty($hashedValue)) {
            return false;
        }

        return $this->make($value) === $hashedValue;
    }

    public function needsRehash($hashedValue, array $options = []): bool
    {
        return false;
    }
}
