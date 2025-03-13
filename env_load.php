<?php
Class EnvLoader
{
    private $env = [];

    public function __construct($path)
    {
        if (!file_exists($path)) {
            throw new Exception('.env file not found');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) { // Skip comments
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $this->env[trim($key)] = trim($value);
        }
    }

    public function get($key, $default = null)
    {
        return $this->env[$key] ?? $default;
    }
}
?>