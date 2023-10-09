<?php

namespace App\Enums;

enum TicketHandler: string
{
    case CUSTOMER = 'Customer';
    case TECHNICAL_SUPPORT = 'Technical Support';
    case HIGH_LEVEL_SUPPORT = 'High level support';
}
