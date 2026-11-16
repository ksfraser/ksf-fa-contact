<?php
/**
 * ksf_FA_Contact — single front controller.
 *
 * URL shape:
 *   index.php                                default tab (contact list)
 *   index.php?view=contacts                  read a tab
 *   index.php?tab=contacts                   alias
 *   index.php?view=contacts&page=3           tab + paging
 *
 * @package ksf_FA_Contact
 * @since   2.0.0
 */

chdir(__DIR__);

if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
}

$path_to_root = "../..";

$frontController = new \Ksfraser\FrontAccounting\Contact\App\ContactAppShell(
    new \Ksfraser\FrontAccounting\Contact\App\ContactFrontController(
        new \Ksfraser\FrontAccounting\Contact\App\ContactAppShell(),
        'SA_CONTACT_VIEW',
        $path_to_root
    )
);

# Actually let's use simpler approach:
require_once __DIR__ . '/includes/common.inc';

$frontController = new \Ksfraser\FrontAccounting\Contact\App\ContactFrontController(
    new \Ksfraser\FrontAccounting\Contact\App\ContactAppShell(),
    'SA_CONTACT_VIEW',
    $path_to_root
);

$page_security = $frontController->getSecurityArea();
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");

$frontController->run();
