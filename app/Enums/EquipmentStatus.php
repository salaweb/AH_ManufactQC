<?php

namespace App\Enums;

enum EquipmentStatus: string
{
    case Pending = 'pending';
    case Ok = 'ok';
    case Defect = 'defect';
    case Observation = 'observation';
}
