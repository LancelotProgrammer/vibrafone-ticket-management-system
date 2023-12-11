<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PDFController extends Controller
{
    public function __invoke(Request $request)
    {
        dd('forbbiden');
        $data = [
            'id' => $request->id
        ];
        $pdf = Pdf::loadView('pdf.ticket', $data);
        return $pdf->stream();
    }
}
