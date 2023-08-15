<?php

namespace Drupal\site_audit\Plugin\SiteAuditCheck;

use Drupal\site_audit\Plugin\SiteAuditCheckBase;

/**
 * Provides the CronLast Check.
 *
 * @SiteAuditCheck(
 *  id = "codebase_sizeall",
 *  name = @Translation("Size of entire site"),
 *  description = @Translation("Determine the size of the site root; does not include remote mounts."),
 *  report = "codebase",
 *  weight = -1,
 * )
 */
class CodebaseSizeAll extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {
    return $this->t('Empty, or unable to determine the size due to a permission error.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {

    return $this->t('Total size: @size_all', [
      '@size_all' => human_filesize($this->registry->size_all_kb * 1024),
    ]);
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {}

  /**
   * {@inheritdoc}.
   */
  public function getAction() {}

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    try {
      exec('du -s -x ' . DRUPAL_ROOT, $result);
      $this->registry->size_all_kb = trim(explode("\t", $result[0])[0]);
      if (!$this->registry->size_all_kb && !is_numeric($this->registry->size_all_kb)) {
        $this->abort = TRUE;
        return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
      }
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }
    catch (Exception $e) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
    }
  }

}
