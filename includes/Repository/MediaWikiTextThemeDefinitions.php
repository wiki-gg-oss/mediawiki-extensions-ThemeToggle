<?php

namespace MediaWiki\Extension\ThemeToggle\Repository;

use InvalidArgumentException;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use TextContent;

class MediaWikiTextThemeDefinitions implements ThemeDefinitionsSource {
    public const TITLE_TEXT = 'Theme-definitions';

    public function __construct(
        private readonly RevisionLookup $revisionLookup,
    ) { }

    public function getTitle() {
        return Title::makeTitle( NS_MEDIAWIKI, self::TITLE_TEXT );
    }

    public function load(): array {
        return $this->parseAllText( $this->fetch() ?? '' );
    }

    public function doesTitleInvalidateCache( Title $title ): bool {
        return $title->getNamespace() === NS_MEDIAWIKI && $this->getTitle()->equals( $title );
    }

    private function fetch(): ?string {
        $result = null;

        $revision = $this->revisionLookup->getRevisionByTitle( $this->getTitle() );

        $useMessageFallback = !$revision || !$revision->getContent( SlotRecord::MAIN )
            || $revision->getContent( SlotRecord::MAIN )->isEmpty();

        if ( $useMessageFallback ) {
            $result = wfMessage( self::TITLE_TEXT )->inLanguage( 'en' )->plain();
        } else {
            $content = $revision->getContent( SlotRecord::MAIN );
            $result = ( $content instanceof TextContent ) ? $content->getText() : null;
        }

        return $result;
    }

    private function parseAllText( string $text ): array {
        $definition = preg_replace( '/<!--.*?-->/s', '', $text );
        $lines = preg_split( '/\r\n|\r|\n/', $definition );

        $themes = [];

        foreach ( $lines as $line ) {
            try {
                $themeInfo = $this->parseThemeOptionsLine( $line );
                if ( $themeInfo ) {
                    $themes[$themeInfo['id']] = $themeInfo;
                }
            } catch ( InvalidArgumentException $ex ) {
                continue;
            }
        }

        return $themes;
    }

    /**
     * @param string $definition
     * @return ?array
     */
    private function parseThemeOptionsLine( string $definition ): ?array {
        $match = [];
        if ( !preg_match(
            '/^\*+ *([a-zA-Z](?:[-_:.\w ]*[a-zA-Z0-9])?)(\s*\[.*?\])?\s*$/',
            $definition,
            $match
        ) ) {
            return null;
        }

        $info = [
            'id' => trim( str_replace( ' ', '_', $match[1] ) ),
        ];

        if ( !isset( $info['id'] ) || empty( $info['id'] ) ) {
            return null;
        }

        if ( str_starts_with( $info['id'], 'light' ) ) {
            $info['kind'] = 'light';
        } elseif ( str_starts_with( $info['id'], 'dark' ) ) {
            $info['kind'] = 'dark';
        }

        if ( isset( $match[2] ) ) {
            $options = trim( $match[2], ' []' );
            foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
                $arr = preg_split( '/\s*=\s*/', $option, 2 );
                $option = $arr[0];
                if ( isset( $arr[1] ) ) {
                    $params = explode( ',', $arr[1] );
                    $params = array_map( 'trim', $params );
                } else {
                    $params = [];
                }

                switch ( $option ) {
                    case 'user-groups':
                        $info['user-groups'] = $params;
                        break;
                    case 'default':
                        $info['default'] = true;
                        break;
                    case 'bundled':
                    case 'in-site-css':
                        $info['in-site-css'] = true;
                        break;
                    case 'kind':
                        $info['kind'] = $params[0];
                        break;
                }
            }
        }

        return $info;
    }
}
