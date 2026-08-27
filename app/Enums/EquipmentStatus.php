<?php

namespace App\Enums;

enum EquipmentStatus: string
{
    case Pending = 'pending';
    case MissingAnswers = 'missing_answers';
    case PendingWithDefects = 'pending_defect';
    case Ok = 'ok';
    case OkWithDefects = 'defect';
}
