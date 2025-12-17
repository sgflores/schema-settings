<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use SgFlores\SchemaSetting\Contracts\ConfigurableInterface;
use SgFlores\SchemaSetting\Items\ConfigurableItem;

class TestEnumSettings implements ConfigurableInterface
{
    public static function getKey(): ?string
    {
        return 'global';
    }

    public static function registerConfigurables(): array
    {
        return [
            ConfigurableItem::make('status')
                ->enum(TestStatusEnum::class)
                ->default(TestStatusEnum::Pending)
                ->label('Status')
                ->description('Current status'),

            ConfigurableItem::make('priority')
                ->enum(TestPriorityEnum::class)
                ->default(TestPriorityEnum::Medium)
                ->label('Priority')
                ->description('Priority level'),

            ConfigurableItem::make('optional_status')
                ->enum(TestStatusEnum::class)
                ->default(null)
                ->label('Optional Status')
                ->description('Optional enum field'),
        ];
    }
}
