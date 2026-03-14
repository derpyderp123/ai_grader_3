<?php
/**
 * Configuration Loader
 * 
 * Loads environment variables from .env file
 */

class Config {
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * Load configuration from .env file
     */
    public static function load(string $path = null): void {
        if (self::$loaded) {
            return;
        }

        $envFile = $path ?? dirname(__DIR__) . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse key=value
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                        $value = substr($value, 1, -1);
                    }
                    
                    putenv("$key=$value");
                    self::$config[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get configuration value
     */
    public static function get(string $key, $default = null) {
        self::load();
        
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Get integer configuration value
     */
    public static function getInt(string $key, int $default = 0): int {
        $value = self::get($key, $default);
        return (int) $value;
    }

    /**
     * Get boolean configuration value
     */
    public static function getBool(string $key, bool $default = false): bool {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }

    /**
     * Get array configuration value (comma-separated)
     */
    public static function getArray(string $key, array $default = []): array {
        $value = self::get($key);
        
        if ($value === false || $value === null) {
            return $default;
        }
        
        return array_map('trim', explode(',', $value));
    }

    /**
     * Check if running in debug mode
     */
    public static function isDebug(): bool {
        return self::getBool('APP_DEBUG', false);
    }

    /**
     * Check if running in production
     */
    public static function isProduction(): bool {
        return self::get('APP_ENV', 'development') === 'production';
    }

    /**
     * Get application URL
     */
    public static function getAppUrl(): string {
        return self::get('APP_URL', 'http://localhost');
    }

    /**
     * Get database configuration as array
     */
    public static function getDatabaseConfig(): array {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'port' => self::get('DB_PORT', '3306'),
            'database' => self::get('DB_DATABASE', 'ai_grading_system'),
            'username' => self::get('DB_USERNAME', 'root'),
            'password' => self::get('DB_PASSWORD', ''),
        ];
    }

    /**
     * Get AI provider configuration
     */
    public static function getAIConfig(): array {
        return [
            'ollama' => [
                'host' => self::get('OLLAMA_HOST', 'http://localhost:11434'),
                'model' => self::get('OLLAMA_MODEL', 'llama2'),
            ],
            'gemini' => [
                'api_key' => self::get('GEMINI_API_KEY'),
            ],
            'openai' => [
                'api_key' => self::get('OPENAI_API_KEY'),
            ],
            'anthropic' => [
                'api_key' => self::get('ANTHROPIC_API_KEY'),
            ],
        ];
    }

    /**
     * Get upload configuration
     */
    public static function getUploadConfig(): array {
        return [
            'max_size' => self::getInt('MAX_FILE_SIZE', 10485760), // 10MB default
            'allowed_extensions' => self::getArray('ALLOWED_EXTENSIONS', ['py', 'java', 'cpp', 'js']),
        ];
    }

    /**
     * Get grading configuration
     */
    public static function getGradingConfig(): array {
        return [
            'timeout' => self::getInt('DEFAULT_TIMEOUT', 60),
            'max_retries' => self::getInt('MAX_RETRY_ATTEMPTS', 3),
        ];
    }
}

// Auto-load configuration
Config::load();
