<?php
require_once __DIR__ . '/../services/OllamaGrader.php';
require_once __DIR__ . '/../services/GeminiGrader.php';
require_once __DIR__ . '/../services/OpenAIGrader.php';
require_once __DIR__ . '/../services/AnthropicGrader.php';

/**
 * GradingManager - Chain of Responsibility Pattern
 * 
 * Manages AI grading with automatic fallback between providers
 * ISO 9126: Reliability - Ensures grading continues even if primary provider fails
 */

class GradingManager {
    private array $graders;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct() {
        $this->maxRetries = Config::getInt('MAX_RETRY_ATTEMPTS', 3);
        $this->retryDelay = 1000000; // 1 second in microseconds
        
        // Initialize graders in priority order
        $this->graders = [
            new OllamaGrader(),      // Priority 1: Local
            new GeminiGrader(),      // Priority 2: Google
            new OpenAIGrader(),      // Priority 3: OpenAI
            new AnthropicGrader(),   // Priority 4: Anthropic
        ];
    }

    /**
     * Grade code with automatic fallback
     * 
     * @param string $code Source code to grade
     * @param array $rubric Grading rubric
     * @return array Grading result with provider info
     * @throws Exception If all providers fail
     */
    public function gradeWithFallback(string $code, array $rubric): array {
        $lastException = null;
        $attemptedProviders = [];

        foreach ($this->graders as $grader) {
            $providerName = $grader->getProviderName();
            
            // Skip if provider is not available
            if (!$grader->isAvailable()) {
                $this->logAttempt($providerName, 'skipped', 'Not configured/available');
                continue;
            }

            $attemptedProviders[] = $providerName;
            $this->logAttempt($providerName, 'attempting', 'Starting grading');

            try {
                // Retry logic for transient failures
                for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
                    try {
                        $startTime = microtime(true);
                        $result = $grader->grade($code, $rubric);
                        $endTime = microtime(true);
                        
                        $responseTime = round(($endTime - $startTime) * 1000, 2);
                        
                        // Update API log with response time
                        $this->updateApiLog($providerName, $grader->getModelInfo(), $responseTime, 200);
                        
                        $this->logAttempt($providerName, 'success', "Completed in {$responseTime}ms");
                        
                        return [
                            'success' => true,
                            'provider' => $providerName,
                            'model' => $grader->getModelInfo(),
                            'response_time_ms' => $responseTime,
                            'attempts' => $attempt,
                            'result' => $result,
                        ];
                        
                    } catch (Exception $e) {
                        $this->logAttempt($providerName, 'retry', "Attempt {$attempt} failed: " . $e->getMessage());
                        
                        if ($attempt < $this->maxRetries) {
                            usleep($this->retryDelay);
                        }
                    }
                }
                
                throw new Exception("All {$this->maxRetries} attempts failed for {$providerName}");
                
            } catch (Exception $e) {
                $lastException = $e;
                $this->logAttempt($providerName, 'failed', $e->getMessage());
                
                // Continue to next provider (fallback)
                continue;
            }
        }

        // All providers failed
        $providersList = implode(', ', $attemptedProviders) ?: 'none';
        $errorMsg = $lastException ? $lastException->getMessage() : 'No providers available';
        
        throw new Exception("All AI providers failed. Attempted: {$providersList}. Error: {$errorMsg}");
    }

    /**
     * Get list of available providers
     */
    public function getAvailableProviders(): array {
        $available = [];
        
        foreach ($this->graders as $grader) {
            $available[] = [
                'name' => $grader->getProviderName(),
                'model' => $grader->getModelInfo(),
                'available' => $grader->isAvailable(),
            ];
        }
        
        return $available;
    }

    /**
     * Check if any provider is available
     */
    public function hasAvailableProvider(): bool {
        foreach ($this->graders as $grader) {
            if ($grader->isAvailable()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Log grading attempt to system logs
     */
    private function logAttempt(string $provider, string $status, string $message): void {
        try {
            $db = Database::getInstance();
            $db->insert('system_logs', [
                'event_type' => $status === 'failed' || $status === 'retry' ? 'warning' : 'info',
                'category' => 'grading',
                'message' => "[{$provider}] {$status}: {$message}",
                'context_json' => json_encode([
                    'provider' => $provider,
                    'status' => $status,
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Failed to log grading attempt: " . $e->getMessage());
        }
    }

    /**
     * Update API usage log with response time
     */
    private function updateApiLog(string $provider, string $model, float $responseTime, int $statusCode): void {
        try {
            $db = Database::getInstance();
            $db->query(
                "UPDATE api_usage_logs SET response_time_ms = :response_time, status_code = :status 
                 WHERE provider = :provider AND model = :model 
                 ORDER BY id DESC LIMIT 1",
                [
                    'response_time' => (int) $responseTime,
                    'status' => $statusCode,
                    'provider' => $provider,
                    'model' => $model,
                ]
            );
        } catch (Exception $e) {
            // Silent fail
        }
    }

    /**
     * Get grading statistics
     */
    public function getStatistics(): array {
        try {
            $db = Database::getInstance();
            
            $stats = $db->fetchAll("
                SELECT 
                    provider,
                    COUNT(*) as total_requests,
                    AVG(response_time_ms) as avg_response_time,
                    SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) as successful_requests
                FROM api_usage_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY provider
            ");
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
}
