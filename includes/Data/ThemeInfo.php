<?php

namespace MediaWiki\Extension\ThemeToggle\Data;

use InvalidArgumentException;

class ThemeInfo {
    public const KIND_LIGHT = 'light';
    public const KIND_DARK = 'dark';
    public const KIND_UNKNOWN = 'unknown';
    
    private const PUBLIC_TO_PRIVATE_FIELD_MAP = [
        'user-groups' => 'userGroups',
        'in-site-css' => 'inSiteCss',
        'bundled' => 'inSiteCss',
    ];

    /** @var string */
    private string $id;
    /** @var string[] Required user groups to advertise. */
    private array $userGroups = [];
    /** @var bool Whether default. */
    private bool $default = false;
    /** @var bool Whether included in site CSS. */
    private bool $inSiteCss = false;
    /** @var string Either dark or light */
    private string $kind = 'unknown';


    public function __construct( array $info ) {
        foreach ( $info as $option => $params ) {
            $mapped = self::PUBLIC_TO_PRIVATE_FIELD_MAP[ $option ] ?? $option;

            switch ( $option ) {
                case 'id':
                case 'user-groups':
                case 'userGroups':
                case 'default':
                case 'in-site-css':
                case 'bundled':
                case 'kind':
                    $this->{$mapped} = $params;
                    break;
                default:
                    throw new InvalidArgumentException( "Unrecognized '$option' parameter" );
            }
        }
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return string The theme kind, i.e. "dark" or "light
     */
    public function getKind(): string {
        return $this->kind;
    }

    /**
     * @return string
     */
    public function getMessageId(): string {
        return 'theme-' . $this->id;
    }

    /**
     * @return string
     */
    public function getCssPageName(): string {
        return 'MediaWiki:Theme-' . $this->id . '.css';
    }

    /**
     * @return bool Whether included in site CSS.
     */
    public function isBundled(): bool {
        return $this->inSiteCss;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool {
        return $this->default;
    }

    /**
     * @return string[]
     */
    public function getEntitledUserGroups(): array {
        return $this->userGroups;
    }

    /**
     * Serialise this gadget to an array.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'default' => $this->default,
            'in-site-css' => $this->inSiteCss,
            'user-groups' => $this->userGroups,
            'kind' => $this->kind,
        ];
    }
}
