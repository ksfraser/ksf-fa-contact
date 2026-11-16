<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Contact\App;

use ksfraser\FrontAccounting\Common\App\AbstractFrontController;

/**
 * ContactFrontController — single front controller.
 *
 * @package ksf_FA_Contact
 * @since   2.0.0
 */
class ContactFrontController extends AbstractFrontController
{
    public function __construct(
        ContactAppShell $appShell,
        string $fallbackSecurityArea,
        string $path_to_root
    ) {
        parent::__construct($appShell, $fallbackSecurityArea, $path_to_root);
    }
}
