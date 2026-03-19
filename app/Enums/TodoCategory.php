<?php

namespace App\Enums;

enum TodoCategory: string
{
    case Security = 'Security';
    case Operational = 'Operational';
    case Documentation = 'Documentation';
    case Compliance = 'Compliance';
}
