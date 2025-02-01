<?php
namespace MediaWiki\Extension\ThemeToggle\Hooks;

use ManualLogEntry;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use WikiPage;

final class CacheManagementHooks implements
    \MediaWiki\Page\Hook\PageDeleteCompleteHook,
    \MediaWiki\Storage\Hook\PageSaveCompleteHook
{
    /** @var ThemeAndFeatureRegistry */
    private ThemeAndFeatureRegistry $registry;

    public function __construct( ThemeAndFeatureRegistry $registry ) {
        $this->registry = $registry;
    }

	/**
	 * @param WikiPage $wikiPage WikiPage modified
	 * @param UserIdentity $user User performing the modification
	 * @param string $summary Edit summary/comment
	 * @param int $flags Flags passed to WikiPage::doUserEditContent()
	 * @param RevisionRecord $revisionRecord New RevisionRecord of the article
	 * @param EditResult $editResult Object storing information about the effects of this edit,
	 *   including which edits were reverted and which edit is this based on (for reverts and null
	 *   edits).
	 * @return bool|void True or no return value to continue or false to stop other hook handlers
	 *    from being called; save cannot be aborted
	 */
    public function onPageSaveComplete(
        $wikiPage,
        $userIdentity,
        $summary,
        $flags,
        $revisionRecord,
        $editResult
    ): void {
        $this->registry->handlePagePurge( $wikiPage->getTitle() );
    }

	/**
	 * @param ProperPageIdentity $page Page that was deleted.
	 *   This object represents state before deletion (e.g. $page->exists() will return true).
	 * @param Authority $deleter Who deleted the page
	 * @param string $reason Reason the page was deleted
	 * @param int $pageID ID of the page that was deleted
	 * @param RevisionRecord $deletedRev Last revision of the deleted page
	 * @param ManualLogEntry $logEntry ManualLogEntry used to record the deletion
	 * @param int $archivedRevisionCount Number of revisions archived during the deletion
	 * @return true|void
	 */
    public function onPageDeleteComplete(
        ProperPageIdentity $page,
        Authority $deleter,
        string $reason,
        int $pageID,
        RevisionRecord $deletedRev,
        ManualLogEntry $logEntry,
        int $archivedRevisionCount
    ): void {
        $this->registry->handlePagePurge( Title::newFromPageIdentity( $page ) );
    }
}
