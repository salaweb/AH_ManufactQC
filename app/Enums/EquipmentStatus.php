<?php

namespace App\Enums;

enum EquipmentStatus: string
{
    case Pending = 'pending';
    case PendingWithDefects = 'pending_defect';
    case Ok = 'ok';
    case OkWithDefects = 'defect';
}
