<?php

namespace Drupal\site_audit\Plugin\SiteAuditCheck;

use Drupal\site_audit\Plugin\SiteAuditCheckBase;

/**
 * Provides the BestPracticesServices Check.
 *
 * @SiteAuditCheck(
 *  id = "best_practices_services",
 *  name = @Translation("sites/default/services.yml"),
 *  description = @Translation("Check if a site-specific services.yml file exists."),
 *  report = "best_practices"
 * )
 */
class BestPracticesServices extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {
    return $this->t('No site-specific services.yml file has been created.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->t('Site-specific services.yml in use.');
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      return $this->t('Use of a site-specific services.yml is unnecessary for most sites, and can cause issues as it overrides core.services.yml. See https://www.drupal.org/project/drupal/issues/2547447.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    // Check if the services.yml file exists.
    if (file_exists(DRUPAL_ROOT . '/sites/default/services.yml')) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
  }

}