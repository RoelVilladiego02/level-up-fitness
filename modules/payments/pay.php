<?php
/**
 * Redirect to Unified Payments Page
 * This file is kept for backward compatibility
 * All payment functionality has been moved to index.php
 */
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
redirect(APP_URL . 'modules/payments/');
?>
