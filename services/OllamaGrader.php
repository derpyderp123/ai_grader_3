<?php
require_once __DIR__ . '/AIGrader.php';

/**
 * Ollama AI Grader
 * 
 * Local LLM provider for code grading
 * Priority: 1 (First attempt in fallback chain)
 */

class OllamaGrader implements AIGrader {
    private string $host;
    private string $model;
    private int $timeout;

    public function __construct() {
        $this->host = Config::get('OLLAMA_HOST', 'http://localhost:11434');
        $this->model = Config::get('OLLAMA_MODEL', 'llama2');
        $this->timeout = Config::getInt('DEFAULT_TIMEOUT', 60);
    }

    /**
     * Grade code using Ollama
     */
    public function grade(string $code, array $rubric): array {
        $prompt = $this->buildPrompt($code, $rubric);
        
        $response = $this->sendRequest($prompt);
        $result = $this->parseResponse($response);
        
        // Log API usage
        $this->logUsage($result);
        
        return $result;
    }

    /**
     * Build the grading prompt
     */
    private function buildPrompt(string $code, array $rubric): string {
        $rubricText = json_encode($rubric, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
You are an expert code reviewer and grader. Analyze the following code submission based on the provided rubric.

RUBRIC:
{$rubricText}

CODE TO GRADE:
```
{$code}
```

Evaluate the code and provide your assessment in STRICT JSON format with this exact structure:
{
    "score": <integer 0-100>,
    "summary": "<brief overall assessment>",
    "correctness": {
        "score": <integer 0-100>,
        "comments": "<analysis of correctness>"
    },
    "efficiency": {
        "score": <integer 0-100>,
        "big_o": "<time complexity analysis>",
        "comments": "<efficiency comments>"
    },
    "security": {
        "score": <integer 0-100>,
        "vulnerabilities": ["<list any security issues>"],
        "comments": "<security analysis>"
    },
    "style": {
        "score": <integer 0-100>,
        "comments": "<code style, naming, comments analysis>"
    },
    "bugs": ["<list any bugs found>"],
    "suggestions": ["<list improvement suggestions>"]
}

IMPORTANT: Output ONLY valid JSON. No markdown, no explanations outside the JSON. Ensure all required fields are present.
PROMPT;
    }

    /**
     * Send request to Ollama API
     */
    private function sendRequest(string $prompt): string {
        $url = rtrim($this->host, '/') . '/api/generate';
        
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.3,
                'top_p' => 0.9,
                'num_predict' => 2048,
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new Exception("Ollama API error: HTTP {$httpCode}. {$error}");
        }

        return $response;
    }

    /**
     * Parse JSON response from Ollama
     */
    private function parseResponse(string $response): array {
        $data = json_decode($response, true);
        
        if (!isset($data['response'])) {
            throw new Exception("Invalid Ollama response format");
        }

        // Extract JSON from response (may contain markdown or extra text)
        $jsonContent = $this->extractJson($data['response']);
        
        $result = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse grading JSON: " . json_last_error_msg());
        }

        return $this->normalizeResult($result);
    }

    /**
     * Extract JSON from response text
     */
    private function extractJson(string $text): string {
        // Try to find JSON between curly braces
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            return $matches[0];
        }
        
        // Remove markdown code blocks if present
        $text = preg_replace('/```(?:json)?\s*/', '', $text);
        $text = preg_replace('/```/', '', $text);
        
        return trim($text);
    }

    /**
     * Normalize result to ensure all required fields exist
     */
    private function normalizeResult(array $result): array {
        return [
            'score' => $result['score'] ?? 0,
            'summary' => $result['summary'] ?? '',
            'correctness' => $result['correctness'] ?? ['score' => 0, 'comments' => ''],
            'efficiency' => $result['efficiency'] ?? ['score' => 0, 'big_o' => '', 'comments' => ''],
            'security' => $result['security'] ?? ['score' => 0, 'vulnerabilities' => [], 'comments' => ''],
            'style' => $result['style'] ?? ['score' => 0, 'comments' => ''],
            'bugs' => $result['bugs'] ?? [],
            'suggestions' => $result['suggestions'] ?? [],
        ];
    }

    /**
     * Log API usage for tracking
     */
    private function logUsage(array $result): void {
        try {
            $db = Database::getInstance();
            $db->insert('api_usage_logs', [
                'provider' => 'ollama',
                'model' => $this->model,
                'status_code' => 200,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Silent fail for logging
        }
    }

    public function getProviderName(): string {
        return 'Ollama';
    }

    public function isAvailable(): bool {
        try {
            $url = rtrim($this->host, '/') . '/api/tags';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getModelInfo(): string {
        return "Ollama - {$this->model} (Local)";
    }
}
