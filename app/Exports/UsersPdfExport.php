<?php

namespace App\Exports;

use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class UsersPdfExport
{
    protected $users;

    public function __construct($users = null)
    {
        $this->users = $users;
    }

    /**
     * Export users to PDF
     *
     * @return string
     */
    public function export()
    {
        $users = $this->users ?? User::with('roles')->get();

        $html = View::make('exports.users', [
            'users' => $users,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
