<?php

declare(strict_types=1);

namespace App\Presentation\Api;

use Nette\Application\UI\Presenter;

final class ApiPresenter extends Presenter
{
    public function actionInvoice(string $id): void
    {
        $this->sendJson(['id' => $id, 'status' => 'paid']);
    }
}
