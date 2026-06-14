<?php

namespace App\Http\Controllers\Account;

use App\ViewModels\AccountDashboardPage;
use Illuminate\Contracts\View\View;

class DashboardController extends AccountController
{
    public function __invoke(AccountDashboardPage $page): View
    {
        return view('account.dashboard', $page->viewData($this->customer()));
    }
}
