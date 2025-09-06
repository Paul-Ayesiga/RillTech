<?php

namespace App\AI\Providers;

use NeuronAI\Providers\OpenAI\OpenAI;

/**
 * Mistral AI Provider
 *
 * Extends OpenAI provider since Mistral AI uses OpenAI-compatible API
 * https://docs.mistral.ai/api/
 */
class MistralAI extends OpenAI
{
    /**
     * Mistral AI API base URI
     */
    protected string $baseUri = "https://api.mistral.ai/v1";

    /**
     * Create a new Mistral AI provider instance
     *
     * @param string $key Mistral AI API key
     * @param string $model Mistral AI model (e.g., mistral-large-latest, mistral-small-latest)
     */
    public function __construct(
        string $key,
        string $model = 'mistral-large-latest'
    ) {
        parent::__construct($key, $model);

        // Note: HTTP timeout configuration will be handled at the controller level
        // to prevent hanging requests and provide better error handling
    }
}
