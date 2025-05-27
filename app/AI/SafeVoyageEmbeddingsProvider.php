<?php

namespace App\AI;

use NeuronAI\RAG\Embeddings\VoyageEmbeddingsProvider;
use Illuminate\Support\Facades\Log;

/**
 * Safe Voyage Embeddings Provider with rate limiting handling
 * 
 * This class extends the base VoyageEmbeddingsProvider to add graceful
 * handling of rate limiting and other API errors.
 */
class SafeVoyageEmbeddingsProvider extends VoyageEmbeddingsProvider
{
    public function embedText(string $text): array
    {
        try {
            return parent::embedText($text);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Check for rate limiting
            if (str_contains($errorMessage, '429') || str_contains($errorMessage, 'Too Many Requests') || str_contains($errorMessage, 'rate limit')) {
                Log::warning('Voyage AI rate limit exceeded', [
                    'error' => $errorMessage,
                    'text_length' => strlen($text)
                ]);
                
                // Throw a specific exception that the agent can catch
                throw new \Exception('RATE_LIMITED: The AI system is currently busy processing other requests. Please try again in a moment.');
            }
            
            // Check for quota exceeded
            if (str_contains($errorMessage, 'quota') || str_contains($errorMessage, 'billing')) {
                Log::warning('Voyage AI quota/billing issue', [
                    'error' => $errorMessage,
                    'text_length' => strlen($text)
                ]);
                
                throw new \Exception('QUOTA_EXCEEDED: The AI system is temporarily unavailable. Please try again later.');
            }
            
            // For other errors, log and re-throw
            Log::error('Voyage AI embeddings error', [
                'error' => $errorMessage,
                'text_length' => strlen($text),
                'error_type' => get_class($e)
            ]);
            
            throw new \Exception('EMBEDDINGS_ERROR: The AI system encountered an error. Please try again.');
        }
    }
}
