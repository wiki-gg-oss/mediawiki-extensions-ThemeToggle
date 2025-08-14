<?php

namespace MediaWiki\Extension\ThemeToggle\ResourceLoader;

use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Module;
use MediaWiki\ResourceLoader\WikiModule;

class WikiThemeModule extends WikiModule {
    /** @var string Theme ID */
    private string $id;

    public function __construct( array $options ) {
        $this->id = $options['id'];
    }

    /**
     * Get list of pages used by this module
     *
     * @param Context $context
     * @return array[]
     */
    protected function getPages( Context $context ) {
        $id = $this->id;
        $skin = ucfirst( $context->getSkin() );
        return [
            "MediaWiki:Theme-$id.css" => [ 'type' => 'style' ],
            "MediaWiki:$skin-theme-$id.css" => [ 'type' => 'style' ],
        ];
    }

    public function isPackaged(): bool {
        return false;
    }

    public function getType() {
        return Module::LOAD_STYLES;
    }

    public function getTargets() {
        return [ 'desktop', 'mobile' ];
    }

    public function getGroup() {
        return 'site';
    }
}
