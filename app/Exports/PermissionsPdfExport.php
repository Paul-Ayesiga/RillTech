<?php

namespace App\Exports;

use App\Models\Permission;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class PermissionsPdfExport
{
    protected $permissions;

    public function __construct($permissions = null)
    {
        $this->permissions = $permissions;
    }

    /**
     * Export permissions to PDF
     *
     * @return string
     */
    public function export()
    {
        $permissions = $this->permissions ?? Permission::with(['group', 'roles'])->get();

        $html = View::make('exports.permissions', [
            'permissions' => $permissions,
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
