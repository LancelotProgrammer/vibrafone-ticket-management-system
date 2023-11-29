<?php

namespace App\Enums;

enum TicketStatus: string
{
    case IN_PROGRESS = 'In Progress';
    case CLOSED = 'Closed';
    case CUSTOMER_PENDING = 'Customer Pending';
    case CUSTOMER_UNDER_MONITORING = 'Customer Under Monitoring';
    case TECHNICAL_SUPPORT_PENDING = 'Technical Support Pending';
    case TECHNICAL_SUPPORT_UNDER_MONITORING = 'Technical Support Under Monitoring';
    case HIGH_TECHNICAL_SUPPORT_PENDING = 'High Technical Support Pending';
    case HIGH_TECHNICAL_SUPPORT_UNDER_MONITORING = 'High Technical Under Monitoring';
    case EXTERNAL_TECHNICAL_SUPPORT_PENDING = 'External Technical Support Pending';
    case EXTERNAL_TECHNICAL_SUPPORT_UNDER_MONITORING = 'External Technical Under Monitoring';
}
