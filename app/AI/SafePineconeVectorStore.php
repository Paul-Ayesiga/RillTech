<?php

namespace App\AI;

use NeuronAI\RAG\VectorStore\PineconeVectorStore;
use NeuronAI\RAG\Document;
use Illuminate\Support\Facades\Log;

/**
 * Safe Pinecone Vector Store with error handling
 *
 * This class extends the base PineconeVectorStore to add error handling
 * for cases where the vector store is empty or returns malformed results.
 */
class SafePineconeVectorStore extends PineconeVectorStore
{
    public function similaritySearch(array $embedding): iterable
    {
        try {
            $result = $this->client->post("query", [
                \GuzzleHttp\RequestOptions::JSON => [
                    'namespace' => '',
                    'includeMetadata' => true,
                    'vector' => $embedding,
                    'topK' => $this->topK,
                ]
            ])->getBody()->getContents();

            $result = \json_decode($result, true);

            // Handle empty results or missing matches
            if (!isset($result['matches']) || empty($result['matches'])) {
                Log::info('Pinecone vector search returned no matches', [
                    'embedding_size' => count($embedding),
                    'topK' => $this->topK
                ]);
                return [];
            }

            return \array_map(function (array $item) {
                $document = new Document();
                $document->id = $item['id'] ?? 'unknown';
                $document->embedding = $item['values'] ?? [];

                // Safely access metadata with fallbacks
                $metadata = $item['metadata'] ?? [];

                // Handle both 'text' and 'content' keys for backward compatibility
                $document->content = $metadata['content'] ?? $metadata['text'] ?? 'No content available';
                $document->sourceType = $metadata['sourceType'] ?? 'document';
                $document->sourceName = $metadata['sourceName'] ?? 'RillTech Knowledge Base';
                $document->score = $item['score'] ?? 0.0;

                return $document;
            }, $result['matches']);

        } catch (\Exception $e) {
            // Log the error and return empty results to allow the agent to continue
            Log::warning('Pinecone vector search failed', [
                'error' => $e->getMessage(),
                'embedding_size' => count($embedding),
                'error_type' => get_class($e)
            ]);
            return [];
        }
    }
}
