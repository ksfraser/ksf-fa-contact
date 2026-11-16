<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\Controller;

use Ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FrontAccounting\Contact\Service\ContactService;
use ksfraser\RelatedTo\DTO\RelatedQueryDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use ksfraser\RelatedTo\UI\DefaultRelatedRecordsRenderer;

/**
 * ContactTabController — controller SRP for the Contact tab.
 *
 * @package ksf_FA_Contact
 * @since   2.0.0
 */
class ContactTabController extends AbstractTabController
{
    private $service;

    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new ContactService();
    }

    protected function getPkField(): string
    {
        return 'id';
    }

    protected function getFieldMetadata(): array
    {
        return [
            'entity'      => 'contact',
            'table'       => '0_ksf_contact_profiles',
            'label'       => 'Contact',
            'labelPlural' => 'Contacts',
            'hookPrefix'  => 'contact',
            'pk'          => 'id',
            'fields'      => [],
            'fk_ddls'   => [],
            'ddlHooks'  => [],
            'tableSettings' => [],
        ];
    }

    protected function listRows(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        return array_slice($this->service->listAll(), $offset, $perPage);
    }

    protected function countRows(): int
    {
        return count($this->service->listAll());
    }

    protected function findRecord(string $pk): ?array
    {
        return null;
    }

    protected function createRecord(array $data): int
    {
        return 0;
    }

    protected function updateRecord(string $pk, array $data): void
    {
    }

    protected function deleteRecord(string $pk): void
    {
    }

    protected function fkOptions(): array
    {
        return [];
    }

    protected function renderSummaryTable(): void
    {
        if (!function_exists('start_table')) {
            return;
        }

        $page = max(1, $this->page());
        $perPage = $this->perPage;
        $rows = $this->listRows($page, $perPage);
        $total = $this->countRows();
        $pageCount = max(1, (int) ceil($total / $perPage));

        start_table(TABLESTYLE2, "width='95%'");
        $th = function (string $label): void {
            echo '<th>' . htmlspecialchars($this->localise($label), ENT_QUOTES) . '</th>';
        };
        echo '<tr>';
        $th(_('Contact Name'));
        $th(_('Email'));
        $th(_('Phone'));
        $th(_('Actions'));
        echo '</tr>';

        if (empty($rows)) {
            echo '<tr><td colspan="5"><em>'
                . htmlspecialchars($this->localise('No contacts yet.'), ENT_QUOTES)
                . '</em></td></tr>';
        }

        foreach ($rows as $i => $row) {
            echo '<tr class="' . ($i % 2 ? 'evenrow' : 'oddrow') . '">';
            echo '<td>' . htmlspecialchars($row['fields']['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['fields']['email'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['fields']['phone'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td><a href="' . htmlspecialchars($row['url'] ?? '#', ENT_QUOTES, 'UTF-8') . '" target="_blank">'
                . htmlspecialchars($this->localise('View'), ENT_QUOTES, 'UTF-8') . '</a></td>'
            . '</tr>';
        }

        $this->renderTableEnd();

        echo '<tr class="navibar"><td colspan="6"><div style="float:right;">';
        echo $this->pagerLink(1, _('First'), $page > 1, $page);
        echo $this->pagerLink($page - 1, _('Prev'), $page > 1, $page);
        echo $this->pagerLink($page + 1, _('Next'), $page < $pageCount, $page);
        echo $this->pagerLink($pageCount, _('Last'), $page < $pageCount, $page);
        echo '</div></td></tr>';

        end_table(0);
    }

    protected function renderNotes(): void
    {
        $lhsId = isset($_GET['id']) ? (string) $_GET['id'] : '';
        if ($lhsId === '' || !function_exists('hook_invoke')) {
            return;
        }

        $queryDto = new RelatedQueryDto('contact', $lhsId);
        $queryResult = $queryDto;
        hook_invoke('ksf_FA_RelatedTo', 'RELATED_TO', $queryResult, null);

        if (!is_array($queryResult) || !isset($queryResult['related']) || !is_array($queryResult['related'])) {
            return;
        }

        $renderer = new DefaultRelatedRecordsRenderer(array(
            'css_class' => 'related-records',
            'header_class' => 'related-header',
            'table_class' => 'related-table',
            'table_border' => true,
            'type_labels' => array('contact' => _('Contacts')),
        ));
        echo $renderer->renderRelatedRecords('contact', $lhsId, $queryResult['related'], array(
            'css_class' => 'related-records',
            'header_class' => 'related-header',
            'table_class' => 'related-table',
            'table_border' => true,
        ));
    }
}
