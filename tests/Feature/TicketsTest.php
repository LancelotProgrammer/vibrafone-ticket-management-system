<?php

use App\Enums\TicketSubWorkOrder;
use App\Enums\TicketWorkOrder;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Resources\TicketResource\Pages\ListTickets;
use App\Models\Ticket;
use App\Models\TicketHistory;
use Filament\Tables\Actions\DeleteAction;
use function Pest\Livewire\livewire;
use Illuminate\Support\Str;

$technicalSupportUserId = 1;
$highTechnicalSupportUserId = 4;
$externalTechnicalSupportUserId = 19;

$newTechnicalSupportUserId = 2;
$newHighTechnicalSupportUserId = 16;
$newExternalTechnicalSupportUserId = 18;

$newDataForCreate = [
    'title' => Str::random(10),
    'description' => Str::random(50),
    'company' => Str::random(10),
    'ne_product' => Str::random(10),
    'sw_version' => fake()->randomElement(['1.0', '2.0', '3.0']),

    'type_id' => 1,
    'priority_id' => 1,
    'department_id' => 2,
    'category_id' => 1,
];

$newDataForEdit = [
    'title' => Str::random(10),
    'description' => Str::random(50),
    'company' => Str::random(10),
    'ne_product' => Str::random(10),
    'sw_version' => fake()->randomElement(['1.0', '2.0', '3.0']),
];

it('can render table page', function () {
    $this->get(TicketResource::getUrl('index'))->assertSuccessful();
});

it('can create', function () use ($newDataForCreate) {

    livewire(CreateTicket::class)
        ->fillForm([
            'title' => $newDataForCreate['title'],
            'description' => $newDataForCreate['description'],
            'company' => $newDataForCreate['company'],
            'ne_product' => $newDataForCreate['ne_product'],
            'sw_version' => $newDataForCreate['sw_version'],

            'type_id' => $newDataForCreate['type_id'],
            'priority_id' => $newDataForCreate['priority_id'],
            'department_id' => $newDataForCreate['department_id'],
            'category_id' => $newDataForCreate['category_id'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Ticket::class, [
        'title' => $newDataForCreate['title'],
        'description' => $newDataForCreate['description'],
        'company' => $newDataForCreate['company'],
        'ne_product' => $newDataForCreate['ne_product'],
        'sw_version' => $newDataForCreate['sw_version'],

        'type_id' => $newDataForCreate['type_id'],
        'priority_id' => $newDataForCreate['priority_id'],
        'department_id' => $newDataForCreate['department_id'],
        'category_id' => $newDataForCreate['category_id'],
    ]);
});

it('can edit', function () use ($newDataForCreate) {

    $ticket = Ticket::where('title', $newDataForCreate['title'])->firstOrFail();
    $this->get(TicketResource::getUrl('edit', [
        'record' => $ticket->id,
    ]))->assertSuccessful();
});

it('can edit and save', function () use ($newDataForCreate, $newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForCreate['title'])->firstOrFail();
    livewire(EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->fillForm([
            'title' => $newDataForEdit['title'],
            'description' => $newDataForEdit['description'],
            'company' => $newDataForEdit['company'],
            'ne_product' => $newDataForEdit['ne_product'],
            'sw_version' => $newDataForEdit['sw_version'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('type_id / priority_id / department_id / category_id are disabled before assgin', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->assertFormFieldIsDisabled('type_id')
        ->assertFormFieldIsDisabled('priority_id')
        ->assertFormFieldIsDisabled('department_id')
        ->assertFormFieldIsDisabled('category_id');
});

test('title / description / company / ne_product / sw_version are enabled before assgin', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->assertFormFieldIsEnabled('title')
        ->assertFormFieldIsEnabled('description')
        ->assertFormFieldIsEnabled('company')
        ->assertFormFieldIsEnabled('ne_product')
        ->assertFormFieldIsEnabled('sw_version');
});

it('can assert start time before can assign', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNull($ticket->start_at);
});

it('can assign', function () use ($newDataForEdit, $technicalSupportUserId) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'assign_ticket',
            [
                'user_id' => $technicalSupportUserId,
            ]
        );
});

it('can assert start time after can assign', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNotNull($ticket->start_at);
});

test('type_id / priority_id / department_id / category_id are disabled after assgin', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->assertFormFieldIsDisabled('type_id')
        ->assertFormFieldIsDisabled('priority_id')
        ->assertFormFieldIsDisabled('department_id')
        ->assertFormFieldIsDisabled('category_id');
});

test('title / description / company / ne_product / sw_version are disabled after assgin', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->assertFormFieldIsDisabled('title')
        ->assertFormFieldIsDisabled('description')
        ->assertFormFieldIsDisabled('company')
        ->assertFormFieldIsDisabled('ne_product')
        ->assertFormFieldIsDisabled('sw_version');
});

// it('can create work order type: FEEDBACK_TO_CUSTOMER | CUSTOMER_INFORMATION_REQUIRED with email and files', function () use ($newDataForEdit) {
// });

it('can create work order type: FEEDBACK_TO_CUSTOMER | CUSTOMER_INFORMATION_REQUIRED', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'create_work_order',
            [
                'work_order' => TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value,
                'sub_work_order' => TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value,
                'body' => 'I am test body',
                'send_email' => false,
            ]
        );

    $this->assertDatabaseHas(TicketHistory::class, [
        'ticket_id' => $ticket->id,
        'work_order' => TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value,
        'owner' => auth()->user()->email,
    ]);
});

it('can create work order type: WORKAROUND_ACCEPTED_BY_CUSTOMER', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
            'record' => $ticket->id,
        ])
        ->callAction(
            'create_work_order',
            [
                'work_order' => TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value,
                'body' => 'I am test body',
                'send_email' => false,
            ]
        );

    $this->assertDatabaseHas(TicketHistory::class, [
        'ticket_id' => $ticket->id,
        'work_order' => TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value,
        'owner' => auth()->user()->email,
    ]);
});

it('can create work order type: CUSTOMER_TROUBLESHOOTING_ACTIVITY', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
            'record' => $ticket->id,
        ])
        ->callAction(
            'create_work_order',
            [
                'work_order' => TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value,
                'body' => 'I am test body',
                'send_email' => false,
            ]
        );

    $this->assertDatabaseHas(TicketHistory::class, [
        'ticket_id' => $ticket->id,
        'work_order' => TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value,
        'owner' => auth()->user()->email,
    ]);
});

it('can assert cancel time before can cancel', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNull($ticket->canceled_at);
});

it('can cancel', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'cancel_ticket'
        );
});

it('can assert cancel time after can cancel', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNotNull($ticket->canceled_at);
});

it('can activate', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(ListTickets::class)
        ->callTableAction('activate_ticket', $ticket);
});

it('can assert cancel time after can activate', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNull($ticket->canceled_at);
});

it('can assert level 1 before escalation to level 2', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertEquals($ticket->level_id, 2);
});

it('can escalate to level 2', function () use ($newDataForEdit, $highTechnicalSupportUserId) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'escalate_ticket_to_high_technical_support',
            [
                'user_id' => $highTechnicalSupportUserId,
            ]
        );
});

it('can assert level 2 after escalation to level 2', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertEquals($ticket->level_id, 3);
});

it('can assert level 2 before escalation to level 3', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertEquals($ticket->level_id, 3);
});

it('can escalate to level 3', function () use ($newDataForEdit, $externalTechnicalSupportUserId) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'escalate_ticket_to_external_technical_support',
            [
                'user_id' => $externalTechnicalSupportUserId,
            ]
        );
});

it('can assert level 2 after escalation to level 3', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertEquals($ticket->level_id, 4);
});

it('can add SL1', function () use ($newDataForEdit, $newTechnicalSupportUserId) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'add_technical_support',
            [
                'user_id' => $newTechnicalSupportUserId,
            ]
        );
});

it('can remove SL1', function () use ($newDataForEdit, $newTechnicalSupportUserId) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'remove_technical_support',
            [
                'user_id' => $newTechnicalSupportUserId
            ]
        );
});

it('can add SL2', function () use ($newDataForEdit, $newHighTechnicalSupportUserId) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'add_high_technical_support',
            [
                'user_id' => $newHighTechnicalSupportUserId,
            ]
        );
});

it('can remove SL2', function () use ($newDataForEdit, $newHighTechnicalSupportUserId) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'remove_high_technical_support',
            [
                'user_id' => $newHighTechnicalSupportUserId
            ]
        );
});

it('can add SL3', function () use ($newDataForEdit, $newExternalTechnicalSupportUserId) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'add_external_technical_support',
            [
                'user_id' => $newExternalTechnicalSupportUserId,
            ]
        );
});

it('can remove SL3', function () use ($newDataForEdit, $newExternalTechnicalSupportUserId) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
            'record' => $ticket->id,
        ])
        ->callAction(
            'remove_external_technical_support',
            [
                'user_id' => $newExternalTechnicalSupportUserId
            ]
        );
});

it('can assert end time before RESOLUTION_ACCEPTED_BY_CUSTOMER', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNull($ticket->end_at);
});

it('can create work order type: RESOLUTION_ACCEPTED_BY_CUSTOMER without email or files', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(TicketResource\Pages\EditTicket::class, [
        'record' => $ticket->id,
    ])
        ->callAction(
            'create_work_order',
            [
                'work_order' => TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value,
                'body' => 'I am test body',
                'send_email' => false,
            ]
        );

    $this->assertDatabaseHas(TicketHistory::class, [
        'ticket_id' => $ticket->id,
        'work_order' => TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value,
        'owner' => auth()->user()->email,
    ]);
});

it('can assert end time after RESOLUTION_ACCEPTED_BY_CUSTOMER', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNotNull($ticket->end_at);
});

it('can assert archive time before can archive', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNull($ticket->deleted_at);
});

it('can archive', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(ListTickets::class)
        ->callTableAction('archive_ticket', $ticket);
});

it('can assert archive time after can archive', function () use ($newDataForEdit) {
    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    $this->assertNotNull($ticket->deleted_at);
});

// it('can export excel', function () use ($newDataForEdit) {
// });
// it('can export pdf', function () use ($newDataForEdit) {
// });
// it('can download files', function () use ($newDataForEdit) {
// });

it('can delete', function () use ($newDataForEdit) {

    $ticket = Ticket::where('title', $newDataForEdit['title'])->firstOrFail();
    livewire(ListTickets::class)
        ->callTableAction(DeleteAction::class, $ticket);
    $this->assertModelMissing($ticket);
});
