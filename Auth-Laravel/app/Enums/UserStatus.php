<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case EXCLUDED = 'EXCLUDED';
    case PENDING = 'PENDING';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::SUSPENDED => 'Suspenso',
            self::EXCLUDED => 'Excluído',
            self::PENDING => 'Pendente',
        };
    }
}
