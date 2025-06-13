<?php

namespace MediaWiki\Extension\ThemeToggle;

final class ThemeToggleConsts {
    public const KIND_TO_CODEX = [
        'dark' => 'night',
        'light' => 'day',
        'unknown' => 'day',
    ];

    public const ALLOWED_SWITCHER_STYLE_VALUES = [
        'dayNight',
        'simple',
        'dropdown',
        'auto',
    ];
}
