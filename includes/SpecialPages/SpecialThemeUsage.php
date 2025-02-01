<?php
/* Based on Extension:Gadgets' SpecialGadgetUsage.php */
namespace MediaWiki\Extension\ThemeToggle\SpecialPages;

use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\QueryPage;

class SpecialThemeUsage extends QueryPage {
    private int $invalidCount = 0;
    private int $invalidActiveCount = 0;

    public function __construct(
        private readonly ExtensionConfig $config,
        private readonly ThemeAndFeatureRegistry $registry
    ) {
        parent::__construct( 'ThemeUsage' );

        $this->limit = 1000;
        $this->shownavigation = false;
    }

    public function isExpensive() {
        return true;
    }

    public function getQueryInfo() {
        return [
            'tables' => [ 'user_properties', 'user', 'querycachetwo' ],
            'fields' => [
                'title' => 'up_value',
                'value' => 'COUNT( user_id )',
                // Need to pick fields existing in the querycache table so that the results are cachable
                'namespace' => 'COUNT( qcc_title )'
            ],
            'conds' => [
                'up_property' => $this->config->getThemePreferenceName()
            ],
            'options' => [
                'GROUP BY' => [ 'up_value' ]
            ],
            'join_conds' => [
                'user' => [
                    'LEFT JOIN', [
                        'up_user = user_id'
                    ]
                ],
                'querycachetwo' => [
                    'LEFT JOIN', [
                        'user_name = qcc_title',
                        'qcc_type = "activeusers"',
                        'up_value = 1'
                    ]
                ]
            ]
        ];
    }

    public function getOrderFields() {
        return [ 'value' ];
    }

    protected function outputTableStart() {
        $html = Html::openElement( 'table', [ 'class' => [ 'sortable', 'wikitable' ] ] );
        $html .= Html::openElement( 'thead', [] );
        $html .= Html::openElement( 'tr', [] );
        $headers = [ 'themeusage-theme', 'themeusage-usercount' ]; //, 'themeusage-activeusers' ];
        foreach ( $headers as $h ) {
            if ( $h === 'themeusage-theme' ) {
                $html .= Html::element( 'th', [], $this->msg( $h )->text() );
            } else {
                $html .= Html::element( 'th', [ 'data-sort-type' => 'number' ], $this->msg( $h )->text() );
            }
        }
        $html .= Html::closeElement( 'tr' );
        $html .= Html::closeElement( 'thead' );
        $html .= Html::openElement( 'tbody', [] );
        $this->getOutput()->addHTML( $html );

        $this->getOutput()->addModuleStyles( 'jquery.tablesorter.styles' );
        $this->getOutput()->addModules( 'jquery.tablesorter' );
    }

    protected function outputTableEnd() {
        $this->getOutput()->addHTML( Html::closeElement( 'tbody' ) . Html::closeElement( 'table' ) );
    }

    public function formatResult( $skin, $result ) {
        $themeId = $result->title;
        $userCount = $this->getLanguage()->formatNum( $result->value );
        if ( $themeId ) {
            if ( !in_array( $themeId, $this->registry->getIds() ) ) {
                $this->invalidCount += $result->value;
                $this->invalidActiveCount += $result->namespace;
                return false;
            }

            $html = Html::openElement( 'tr', [] );
            $html .= Html::element( 'td', [], $themeId );
            $html .= Html::element( 'td', [], $userCount );
            // $html .= Html::element( 'td', [], $this->getLanguage()->formatNum( $result->namespace ) );
            $html .= Html::closeElement( 'tr' );
            return $html;
        }
        return false;
    }

    protected function outputResults( $out, $skin, $dbr, $res, $num, $offset ) {
        if ( $num > 0 ) {
            $this->outputTableStart();
            foreach ( $res as $row ) {
                $line = $this->formatResult( $skin, $row );
                if ( $line ) {
                    $out->addHTML( $line );
                }
            }

            $unknownRow = Html::openElement( 'tr', [] );
            $unknownRow .= Html::openElement( 'td', [] );
            $unknownRow .= Html::element( 'span', [
                'style' => 'border-bottom: 2px dotted #666; font-style: italic',
                'title' => $this->msg( 'themeusage-unknown-theme-tip' )
            ], $this->msg( 'themeusage-unknown-theme' ) );
            $unknownRow .= Html::closeElement( 'td' );
            $unknownRow .= Html::element( 'td', [], $this->getLanguage()->formatNum( $this->invalidCount ) );
            // $unknownRow .= Html::element( 'td', [], $this->getLanguage()->formatNum( $this->invalidActiveCount ) );
            $unknownRow .= Html::closeElement( 'tr' );
            $out->addHTML( $unknownRow );

            $this->outputTableEnd();
        } else {
            $out->addHtml(
                $this->msg( 'themeusage-noresults' )->parseAsBlock()
            );
        }
    }

    protected function getGroupName() {
        return 'wiki';
    }
}
