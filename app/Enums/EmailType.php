<?php

namespace App\Enums;

enum EmailType: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'Customer';
    case TECHNICAL_SUPPORT = 'Technical Support';
    case HIGH_TECHNICAL_SUPPORT = 'High Technical Support';
}
