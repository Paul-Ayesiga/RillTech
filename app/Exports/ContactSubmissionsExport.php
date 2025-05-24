<?php

namespace App\Exports;

use App\Models\ContactSubmission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContactSubmissionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = ContactSubmission::with('assignedUser');

        // Apply filters
        if (isset($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['priority']) && $this->filters['priority'] !== 'all') {
            $query->where('priority', $this->filters['priority']);
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
            'Name',
            'Email',
            'Phone',
            'Company',
            'Subject',
            'Message',
            'Status',
            'Priority',
            'Source',
            'Assigned To',
            'Submitted At',
            'Responded At',
            'IP Address',
        ];
    }

    public function map($submission): array
    {
        return [
            $submission->id,
            $submission->name,
            $submission->email,
            $submission->phone ?? 'N/A',
            $submission->company ?? 'N/A',
            $submission->subject,
            $submission->message,
            ucfirst($submission->status),
            ucfirst($submission->priority),
            $submission->source ?? 'N/A',
            $submission->assignedUser?->name ?? 'Unassigned',
            $submission->created_at?->format('Y-m-d H:i:s'),
            $submission->responded_at?->format('Y-m-d H:i:s') ?? 'N/A',
            $submission->ip_address ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
