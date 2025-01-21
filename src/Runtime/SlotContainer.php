<?php

namespace Stillat\Dagger\Runtime;

use Illuminate\View\ComponentSlot;

class SlotContainer
{
    const DEFAULT_SLOT_KEY = '-default-';

    protected static ?ComponentSlot $emptySlot = null;

    protected array $contents = [];

    public static function getEmptySlot(): ComponentSlot
    {
        if (static::$emptySlot === null) {
            static::$emptySlot = new ComponentSlot;
        }

        return static::$emptySlot;
    }

    public function setSlotContent(string $slotName, string $contents, array $attributes = []): void
    {
        $this->contents[$slotName] = new ComponentSlot($contents, $attributes);
    }

    public function setForwardedSlot(string $slotName, $value, array $attributes = []): void
    {
        if ($slotName === 'default') {
            $slotName = self::DEFAULT_SLOT_KEY;
        }

        $this->contents[$slotName] = $value;
    }

    public function setDefaultContent(string $content): void
    {
        $this->contents[self::DEFAULT_SLOT_KEY] = $content;
    }

    public function hasDefaultSlotContent(): bool
    {
        return array_key_exists(self::DEFAULT_SLOT_KEY, $this->contents);
    }

    public function getDefaultContent(): ComponentSlot
    {
        return new ComponentSlot($this->contents[self::DEFAULT_SLOT_KEY]);
    }

    public function __get(string $name)
    {
        return $this->contents[$name] ?? null;
    }

    public function hasSlot(string $slotName): bool
    {
        return array_key_exists($slotName, $this->contents);
    }
}
