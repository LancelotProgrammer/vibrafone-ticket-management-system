<?php

namespace App\Enums;

enum TicketSubWorkOrder: string
{
    case CUSTOMER_INFORMATION_REQUIRED = 'Customer Information Required';
    case WORKAROUND_CUSTOMER_INFORMATION = 'Workaround Customer Information';
    case FINAL_CUSTOMER_INFORMATION = 'Final Customer Information';
    case TECHNICAL_SUPPORT_INFORMATION_REQUIRED = 'Technical Support Information Required';
    case WORKAROUND_TECHNICAL_SUPPORT_INFORMATION = 'Workaround Technical Support Information';
    case FINAL_TECHNICAL_SUPPORT_INFORMATION = 'Final Technical Support Information';
}
