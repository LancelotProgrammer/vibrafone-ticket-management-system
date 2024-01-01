<!DOCTYPE html>
<html>

<head>
    <title>Export-PDF</title>
    <style>
        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    <h2>Ticket: {{ $ticket->ticket_identifier }} - {{ $ticket->title }}</h2>

    <hr>

    <h2>Ticket Data:</h2>
    <h4>Company: {{ $ticket->company }}</h4>
    <h4>NE product: {{ $ticket->ne_product }}</h4>
    <h4>SW version: {{ $ticket->sw_version }}</h4>
    <h4>Description: {{ $ticket->description }}</h4>

    <hr>

    <h2>Ticket Meta Data:</h2>
    <h4>Department: {{ $ticket->department->title }}</h4>
    <h4>Type: {{ $ticket->type->title }}</h4>
    <h4>Priority: {{ $ticket->priority->title }}</h4>
    <h4>Category: {{ $ticket->category->title }}</h4>
    <h4>Started at: {{ $ticket->start_at ?? 'no date' }}</h4>
    <h4>Ended at: {{ $ticket->end_at ?? 'no date' }}</h4>
    <h4>Canceled at: {{ $ticket->cancel_at ?? 'no date' }}</h4>
    <h4>Escalated to SL2 at: {{ $ticket->escalated_to_high_technical_support_at ?? 'no date' }}</h4>
    {{-- <h4>Escalated to SL3 at: {{ $ticket->escalated_to_external_technical_support_at ?? 'no date' }}</h4> --}}

    <div class="page-break"></div>
    <h2>Ticket Users</h2>
    <hr>
    <h4>Customers Count: {{ $ticket->customer->count() }}</h4>
    @foreach ($ticket->customer as $customer)
        <h4>Customer: {{ $customer->email }}</h4>
    @endforeach
    <h4>SL1 Count: {{ $ticket->technicalSupport->count() }}</h4>
    @foreach ($ticket->technicalSupport as $technicalSupport)
        <h4>SL1: {{ $technicalSupport->email }}</h4>
    @endforeach
    <h4>SL2 Count: {{ $ticket->highTechnicalSupport->count() }}</h4>
    @foreach ($ticket->highTechnicalSupport as $highTechnicalSupport)
        <h4>SL2: {{ $highTechnicalSupport->email }}</h4>
    @endforeach
    {{-- <h4>SL3 Count: {{ $ticket->externalTechnicalSupport->count() }}</h4>
    @foreach ($ticket->externalTechnicalSupport as $externalTechnicalSupport)
        <h4>SL3: {{ $externalTechnicalSupport->email }}</h4>
    @endforeach
    <hr> --}}

    <div class="page-break"></div>

    <h2>Work Order Flow and Ticket History</h2>
    <hr>
    @foreach ($ticketHistories as $ticketHistory)
        @if (!is_null($ticketHistory->work_order))
            @if (!is_null($ticketHistory->sub_work_order))
                <h4> At {{ $ticketHistory->created_at }}: {{ $ticketHistory->work_order }} -
                    {{ $ticketHistory->sub_work_order }} has been created </h4>
            @else
                <h4> At {{ $ticketHistory->created_at }}: {{ $ticketHistory->work_order }} has been created </h4>
            @endif
            <h4> By {{ $ticketHistory->owner }} </h4>
            @if (!is_null($ticketHistory->body))
                <h4> Body: {{ $ticketHistory->body }} </h4>
            @else
                <h4> No Body </h4>
            @endif
            @if (!is_null($ticketHistory->attachments))
                @if (count($ticketHistory->attachments) > 0)
                    <h4> Files: </h4>
                    @foreach ($ticketHistory->attachments as $attachment)
                        <h4> {{ $attachment }} </h4>
                    @endforeach
                @else
                    <h4> No Files Has Been attached </h4>
                @endif
            @endif
        @else
            <h4> At {{ $ticketHistory->created_at }}: {{ $ticketHistory->title }} </h4>
            <h4> By {{ $ticketHistory->owner }} </h4>
        @endif
        <hr>
    @endforeach

</body>

</html>
