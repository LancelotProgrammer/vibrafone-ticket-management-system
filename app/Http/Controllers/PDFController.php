<?php

namespace App\Http\Controllers;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PDFController extends Controller
{
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'integer|required|exists:tickets,id',
        ]);
        if ($validator->fails()) {
            abort(404);
        }
        if (auth()->user()->can('can_export_pdf_ticket') === false) {
            abort(403);
        }
        if (TicketResource::canEdit(Ticket::where('id', $request->id)->first()) === false) {
            abort(403);
        }
        return Pdf::loadView(
            'pdf.ticket',
            [
                'ticket' => Ticket::where('id', $request->id)
                    ->with(
                        'customer',
                        'technicalSupport',
                        'highTechnicalSupport',
                        'externalTechnicalSupport',
                    )->first(),
                'ticketHistories' => TicketHistory::where('ticket_id', $request->id)
                    ->get(),
            ]
        )->stream();
    }
}
