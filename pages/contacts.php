<?php

declare(strict_types=1);

use Ksfraser\FrontAccounting\Contact\Service\ContactService;

$path_to_root = '../../..';

include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    display_error(_('Contact module dependencies are not installed'));
    exit;
}
require_once $autoload;

$service = new ContactService();

$view = isset($_GET['view']) ? (string) $_GET['view'] : 'list';

if ($view === 'list') {
    page(_('Contacts'));
    echo '<p>' . _('Contacts') . '</p>';
    echo '<p>' . _('Contact management is accessed through Related To displays.') . '</p>';
    end_page();
    exit;
}

display_error(_('Unknown view'));
exit;
