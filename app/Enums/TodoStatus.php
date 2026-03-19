<?php

namespace App\Enums;

enum TodoStatus: string
{
    case Pending = 'Pending';
    case InProgress = 'In Progress';
    case Completed = 'Completed';
}
