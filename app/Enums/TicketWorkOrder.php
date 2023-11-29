<?php

namespace App\Enums;

enum TicketWorkOrder: string
{
    case FEEDBACK_TO_CUSTOMER = 'Feedback to Customer';
    case CUSTOMER_TROUBLESHOOTING_ACTIVITY = 'Customer Troubleshooting Activity';
    case CUSTOMER_RESPONSE = 'Customer Response';
    case WORKAROUND_ACCEPTED_BY_CUSTOMER = 'Workaround Accepted By Customer';
    case RESOLUTION_ACCEPTED_BY_CUSTOMER = 'Resolution Accepted by Customer';
    case FEEDBACK_TO_TECHNICAL_SUPPORT = 'Feedback to Technical Support';
    case TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY = 'Technical Support Troubleshooting Activity';
    case TECHNICAL_SUPPORT_RESPONSE = 'Technical Support Response';
    case WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT = 'Workaround Accepted by Technical Support';
    case RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT = 'Resolution Accepted by Technical Support';
    case FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT = 'Feedback to HIGH Technical Support';
    case HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY = 'HIGH Technical Support Troubleshooting Activity';
    case HIGH_TECHNICAL_SUPPORT_RESPONSE = 'HIGH Technical Support Response';
    case WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT = 'Workaround Accepted by HIGH Technical Support';
    case RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT = 'Resolution Accepted by HIGH Technical Support';
}
