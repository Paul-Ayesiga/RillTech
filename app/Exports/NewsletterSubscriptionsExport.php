<?php

namespace App\Exports;

use App\Models\NewsletterSubscription;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NewsletterSubscriptionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = NewsletterSubscription::query();

        // Apply filters
        if (isset($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Email',
            'Name',
            'Status',
            'Source',
            'Subscribed At',
            'Unsubscribed At',
            'IP Address',
        ];
    }

    public function map($subscription): array
    {
        return [
            $subscription->id,
            $subscription->email,
            $subscription->name ?? 'N/A',
            ucfirst($subscription->status),
            $subscription->source ?? 'N/A',
            $subscription->subscribed_at?->format('Y-m-d H:i:s'),
            $subscription->unsubscribed_at?->format('Y-m-d H:i:s') ?? 'N/A',
            $subscription->ip_address ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
