<?php

namespace App\Enums;

enum TicketStatus: string
{
    case IN_PROGRESS = 'In Progress';
    case CUSTOMER_PENDING = 'Customer Pending';
    case CUSTOMER_UNDER_MONITORING = 'Customer Under Monitoring';
    case CLOSED = 'Closed';
    case HIGHT_LEVEL_SUPPORT_PENDING = 'Hight Level Support Pending';
    case TECHNICAL_SUPPORT_PENDING = 'Technical Support Pending';
    case TECHNICAL_SUPPORT_UNDER_MONITORING = 'Technical_Support Under Monitoring';
}
