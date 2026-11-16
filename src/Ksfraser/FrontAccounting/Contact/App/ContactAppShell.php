<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\Menu\FAModuleMenu;
use ksfraser\FrontAccounting\Contact\Controller\ContactTabController;

/**
 * ContactAppShell — application shell SRP.
 *
 * @package ksf_FA_Contact
 * @since   2.0.0
 */
class ContactAppShell extends AbstractAppShell
{
    public function __construct()
    {
        parent::__construct('contact', 'index.php', 'view', 'contacts');

        $this->add_module(_("Contact"));

        $menu = new FAModuleMenu(
            'modules/ksf_FA_Contact/index.php',
            'view',
            ''
        );

        $menu->addItem('contacts', _("Contacts"), MENU_INQUIRY)
             ->registerWithApp($this, 'SA_CONTACT_VIEW');

        $this->add_extensions();
    }
}
