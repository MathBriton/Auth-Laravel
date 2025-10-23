<?php

namespace App\Enums;

enum UserType: string
{
    case PRE_REGISTRATION = 'PRE_REGISTRATION';
    case MEMBER = 'MEMBER';
    case MASTER = 'MASTER';
    case ADMIN = 'ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::PRE_REGISTRATION => 'Pré-cadastro',
            self::MEMBER => 'Membro',
            self::MASTER => 'Master',
            self::ADMIN => 'Administrador',
        };
    }
}
