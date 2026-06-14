<?php

namespace App\Http\Controllers\Account;

use App\Actions\RecordCustomerEnquiryReply;
use App\Models\Enquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends AccountController
{
    public function index(): View
    {
        $enquiries = $this->customer()->enquiries()
            ->with(['product', 'latestMessage'])
            ->withCount('messages')
            ->get();

        return view('account.messages.index', ['enquiries' => $enquiries]);
    }

    public function show(Enquiry $enquiry): View
    {
        $enquiry = $this->ownedEnquiry($enquiry)->load(['messages', 'product']);

        return view('account.messages.show', ['enquiry' => $enquiry]);
    }

    public function reply(Enquiry $enquiry, Request $request, RecordCustomerEnquiryReply $action): RedirectResponse
    {
        $enquiry = $this->ownedEnquiry($enquiry);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $action->handle($enquiry, $data['body']);

        return redirect()->route('account.messages.show', $enquiry)
            ->with('account_status', "Thanks — we've got your message and will be in touch.");
    }
}
