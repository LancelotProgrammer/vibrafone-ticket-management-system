<?php

namespace App\Http\Controllers;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class DownloadTicketFilesController extends Controller
{
    public function redirectToDownloadTicketFiles(Request $request)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'integer|required|exists:tickets,id',
        ]);
        if ($validator->fails()) {
            abort(404);
        }
        if (auth()->user()->can('can_download_all_files_ticket') === false) {
            abort(403);
        }
        $ticket = Ticket::where('id', $request->id)->first();
        if (TicketResource::canEdit($ticket) === false) {
            abort(403);
        }
        $files = $ticket->getMedia();
        if (count($files) === 0) {
            Notification::make()
                ->title('No files to download')
                ->warning()
                ->send();
            return redirect()->back();
        }
        return view('Files.ticket-files-download', [
            'id' => $request->id,
        ]);
    }

    public function downloadTicketFiles(Request $request)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'integer|required|exists:tickets,id',
        ]);
        if ($validator->fails()) {
            abort(404);
        }
        if (auth()->user()->can('can_download_all_files_ticket') === false) {
            abort(403);
        }
        $ticket = Ticket::where('id', $request->id)->first();
        if (TicketResource::canEdit($ticket) === false) {
            abort(403);
        }
        $files = $ticket->getMedia();
        if (count($files) === 0) {
            Notification::make()
                ->title('No files to download')
                ->warning()
                ->send();
            return redirect()->back();
        }
        $zip = new ZipArchive();
        $fileName = 'ticket-' . $ticket->ticket_identifier . '-attachments.zip';
        $zip->open($fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $file) {
            $filePath = storage_path('app/public/' . $file);
            $zip->addFile($filePath, $file);
        }
        $zip->close();
        return response()->download($fileName)->deleteFileAfterSend(true);
    }
}
