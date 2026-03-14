<?php
/**
 * AIGrader Interface
 * 
 * Defines the contract for AI grading providers
 * ISO 9126: Maintainability - Modular design for easy extension
 */

interface AIGrader {
    /**
     * Grade code based on rubric
     * 
     * @param string $code The source code to grade
     * @param array $rubric The grading rubric criteria
     * @return array Grading result with score, feedback, bugs, suggestions
     * @throws Exception If grading fails
     */
    public function grade(string $code, array $rubric): array;

    /**
     * Get the provider name
     */
    public function getProviderName(): string;

    /**
     * Check if this provider is available/configured
     */
    public function isAvailable(): bool;

    /**
     * Get model information
     */
    public function getModelInfo(): string;
}
