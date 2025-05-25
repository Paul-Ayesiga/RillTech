<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBulkInvoiceDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times on failure

    protected $user;
    protected $invoiceIds;
    protected $downloadId;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $invoiceIds, string $downloadId)
    {
        $this->user = $user;
        $this->invoiceIds = $invoiceIds;
        $this->downloadId = $downloadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting bulk invoice download job', [
            'user_id' => $this->user->id,
            'download_id' => $this->downloadId,
            'invoice_count' => count($this->invoiceIds)
        ]);

        try {
            $downloadUrls = [];
            $successCount = 0;
            $failureCount = 0;

            // Process each invoice
            foreach ($this->invoiceIds as $invoiceId) {
                try {
                    $invoice = $this->user->findInvoice($invoiceId);

                    if (!$invoice) {
                        Log::warning("Invoice not found during bulk download", [
                            'user_id' => $this->user->id,
                            'invoice_id' => $invoiceId
                        ]);
                        $failureCount++;
                        continue;
                    }

                    if ($invoice->invoice_pdf) {
                        // Ensure amount is numeric before division
                        $totalAmount = is_numeric($invoice->total()) ? (float)$invoice->total() : 0;

                        $downloadUrls[] = [
                            'id' => $invoice->id,
                            'number' => $invoice->number ?? "Invoice {$invoice->id}",
                            'url' => $invoice->invoice_pdf,
                            'date' => $invoice->date()->toFormattedDateString(),
                            'amount' => $totalAmount / 100,
                            'currency' => strtoupper($invoice->currency)
                        ];

                        $successCount++;

                        Log::info("Invoice processed for bulk download", [
                            'invoice_id' => $invoiceId,
                            'invoice_number' => $invoice->number
                        ]);
                    } else {
                        Log::warning("Invoice has no PDF available", [
                            'invoice_id' => $invoiceId,
                            'invoice_number' => $invoice->number ?? 'No number'
                        ]);
                        $failureCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing invoice in bulk download", [
                        'invoice_id' => $invoiceId,
                        'error' => $e->getMessage()
                    ]);
                    $failureCount++;
                    continue;
                }
            }

            // Store the results
            $this->storeDownloadResults($downloadUrls, $successCount, $failureCount);

            Log::info('Bulk invoice download job completed', [
                'user_id' => $this->user->id,
                'download_id' => $this->downloadId,
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk invoice download job failed', [
                'user_id' => $this->user->id,
                'download_id' => $this->downloadId,
                'error' => $e->getMessage()
            ]);

            // Store failure result
            $this->storeDownloadResults([], 0, count($this->invoiceIds), $e->getMessage());

            throw $e; // Re-throw to trigger job retry
        }
    }

    /**
     * Store the download results for later retrieval
     */
    private function storeDownloadResults(array $downloadUrls, int $successCount, int $failureCount, ?string $error = null): void
    {
        $result = [
            'download_id' => $this->downloadId,
            'user_id' => $this->user->id,
            'status' => $error ? 'failed' : 'completed',
            'download_urls' => $downloadUrls,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'total_requested' => count($this->invoiceIds),
            'error' => $error,
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addHours(24)->toISOString() // Results expire after 24 hours
        ];

        // Store in cache for 24 hours
        cache()->put("bulk_download_{$this->downloadId}", $result, now()->addHours(24));

        Log::info('Bulk download results stored', [
            'download_id' => $this->downloadId,
            'status' => $result['status'],
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk invoice download job failed permanently', [
            'user_id' => $this->user->id,
            'download_id' => $this->downloadId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Store failure result
        $this->storeDownloadResults([], 0, count($this->invoiceIds), $exception->getMessage());
    }
}
