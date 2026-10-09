# 010 — A paid gift-voucher purchase shows "waiting to confirm your payment" forever

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the voucher purchase entries and the email audit's
Discussion) and `audits/reports/email-audit.md` (Discussion, follow-up E-3). **Size: small to medium.**

`git checkout main && git pull` → `git checkout -b fix/voucher-payment-success`.

> **Why this exists.** The email audit found it in passing (follow-up E-3, code read):
> - after a gift-voucher purchase, `App\ViewModels\PaymentSuccessPage` has no booking to show;
> - so a **paid** voucher payment shows "Almost there — we're waiting to confirm your card payment. Refresh"
>   indefinitely.
>
> A customer who has just paid is told it hasn't gone through. That's a real customer-facing defect before
> launch.
>
> **Premise from a code read, not verified by doing.** Reproduce it first: buy a voucher locally with Stripe
> test mode (or the existing payment test helpers), deliver the `checkout.session.completed` webhook, and load
> the success URL. If it already shows a correct voucher confirmation, say so and stop.
>
> Code to read: `PaymentSuccessPage.php:17-37` resolves `payment → booking` or `booking → payment` and returns
> `{payment, booking}`. A voucher payment has `vouchers.payment_id` and no booking.

## Build

- Let the success page recognise a voucher payment, through the payment's voucher (`IssuePurchasedVoucher`
  links them). Show a paid confirmation that names the voucher, the value, who it's for, and that the voucher
  email is on its way. Use the existing success-page components and copy style.
- Keep the "waiting" state for a payment that genuinely hasn't been confirmed by the webhook yet, for
  vouchers too.

## Rules

- No change to booking success pages, payment handling or the webhook Actions.
- The page must never show another customer's voucher. Resolve it the way the booking path resolves its
  session or token, and test it with a second voucher.

## Tests

- A paid voucher payment → paid confirmation with the voucher details. Red on current `main`.
- An unpaid voucher payment → waiting state.
- Another customer's voucher isn't reachable.

## Finish

`composer check` green. Screenshots of both states at 1440 and 390 (cropped JPEGs) in `audits/reports/`.
DECISIONS entry. Push the branch. **Do not merge** (unless on an authorised unattended run).
