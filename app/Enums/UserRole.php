<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Qc = 'qc';
    case Operari = 'operari';
    case OperariProduccio = 'operari_produccio';
}
