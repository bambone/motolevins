<?php

namespace App\NotificationCenter;

enum NotificationSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';
    case Digest = 'digest';

    /**
     * @return list<self>
     */
    public static function orderedHighestFirst(): array
    {
        return [self::Critical, self::High, self::Normal, self::Low, self::Digest];
    }

    public function rank(): int
    {
        return match ($this) {
            self::Critical => 5,
            self::High => 4,
            self::Normal => 3,
            self::Low => 2,
            self::Digest => 1,
        };
    }

    public function isAtLeast(self $min): bool
    {
        return $this->rank() >= $min->rank();
    }

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
