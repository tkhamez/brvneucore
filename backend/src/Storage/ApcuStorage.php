<?php

namespace Neucore\Storage;

use Neucore\Exception\RuntimeException;

class ApcuStorage implements StorageInterface
{
    const PREFIX = '__neucore__';

    public function set(string $key, string $value): bool
    {
        if (mb_strlen($key) > 112 || mb_strlen($value) > 255) {
            throw new RuntimeException('String too long.');
        }

        return (bool) apcu_store(self::PREFIX . $key, $value);
    }

    public function get(string $key): ?string
    {
        $value = apcu_fetch(self::PREFIX . $key);

        if ($value === false) {
            return null;
        }

        return (string) $value;
    }
}
