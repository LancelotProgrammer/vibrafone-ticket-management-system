<?php

namespace App\Enums;

enum TicketHandler: string
{
    case CUSTOMER = 'Customer';
    case TECHNICAL_SUPPORT = 'Technical Support';
    case HIGH_TECHNICAL_SUPPORT = 'High Technical Support';
    case EXTERNAL_TECHNICAL_SUPPORT = 'External Technical Support';
}
