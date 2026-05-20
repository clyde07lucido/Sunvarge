<?php
if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);

    $envPath = __DIR__ . '/.env';

    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $_ENV[trim($key)] = trim($value);
        }
    }
}
?>
