<?php

declare(strict_types=1);

use Ksfraser\FrontAccounting\Contact\Service\ContactService;
use ksfraser\RelatedTo\DTO\RelatedQueryDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use ksfraser\RelatedTo\UI\DefaultRelatedRecordsRenderer;

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

    // Related records rendered via RelatedTo hook
    $lhsId = isset($_GET['id']) ? (string) $_GET['id'] : '';
    if (function_exists('hook_invoke') && class_exists(DefaultRelatedRecordsRenderer::class) && $lhsId !== '') {
        $queryDto = new RelatedQueryDto('contact', $lhsId);
        $queryResult = $queryDto;
        hook_invoke('ksf_FA_RelatedTo', 'RELATED_TO', $queryResult, null);
        if (is_array($queryResult) && isset($queryResult['related']) && is_array($queryResult['related'])) {
            $renderer = new DefaultRelatedRecordsRenderer(array(
                'label_resolver' => function ($type, $id) { return (string) $id; },
                'url_resolver'   => function ($type, $id) { return '#'; },
                'type_labels'    => array('contact' => _('Contacts')),
            ));
            echo $renderer->renderRelatedRecords('contact', $lhsId, $queryResult['related'], array(
                'css_class' => 'related-records',
                'header_class' => 'related-header',
                'table_class' => 'related-table',
                'table_border' => true,
            ));
        }
    }
    end_page();
    exit;
}

display_error(_('Unknown view'));
exit;
