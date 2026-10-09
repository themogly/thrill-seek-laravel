# Foreign-key delete rules — Phase 1 proposal (for Ben's approval)

Prompt `prompts/008-database-refuses-money-linked-deletes.md`, Phase 1 (unattended run 2, item 4). Branch
`fix/fk-delete-rules` off `main` = `5d5847a`. **Proposal only. Nothing is built.** Phase 2 runs only after
Ben approves or edits this table, with the approval recorded in DECISIONS.

**Source of truth for "current":** the live schema. I read `information_schema` on the local MySQL 8
database, migrated from these migrations: **32 foreign keys.** (`sessions.user_id` is an index, not an FK.)

**Delete paths found** (grep of `app/`, `routes/` and `database/seeders/` for `->delete(`, `forceDelete`,
`destroy`, `truncate`, `DeleteAction`, bulk actions):
- **Guarded** by `AdminActions::guardedDelete()` / `guardedBulkDelete()`: Product, Booking, CourseDate,
  TandemDate, Location, Voucher, Discipline.
- **Unguarded** Filament deletes (content only): Testimonial, HallOfFameEntry, Faq, News, Instructor,
  GalleryImage, ShopItem, Document, NewsletterSubscriber, CourseReminder (only while unsent).
- **`EraseCustomerData`** deletes the customer's reviews, their newsletter subscriber row and their login
  links. It **anonymises** (doesn't delete) the customer, their bookings, payments, enquiries and vouchers.
- **No delete path at all:** Customer, Enquiry, Payment, User, NewsletterCampaign, EnquiryMessage.
- **Seeders:** only `EmailTemplate::where('key', 'gift_voucher')->delete()` (no FK).

## The table

Legend for "money/history": **$** money or a payment; **B** a booking; **H** the customer's history
(enquiries, messages, reviews).

| # | Child → parent | Current | Money/history on parent? | Who deletes the parent | **Proposed** | Reasoning |
|---|---|---|---|---|---|---|
| 1 | `payments.booking_id` → bookings | SET NULL | $ B | Booking Delete (guarded) | **RESTRICT** | A payment must never lose its booking. The audit proved deleting a booking orphans its paid payment. |
| 2 | `vouchers.booking_id` → bookings | SET NULL | $ B | Booking Delete (guarded) | **RESTRICT** | A redeemed voucher's booking is the record of where the money went. |
| 3 | `bookings.course_date_id` → course_dates | SET NULL | B | CourseDate Delete (guarded) | **RESTRICT** | A student's booking must not silently lose its course. |
| 4 | `course_messages.course_date_id` → course_dates | CASCADE | H | CourseDate Delete (guarded) | keep **CASCADE** | Messages only go to a course's bookings, and #3 already refuses deleting a course that has any. A deletable course has no recipients, so its messages and reminders are pure child data. |
| 5 | `course_reminders.course_date_id` → course_dates | CASCADE | — | CourseDate Delete | keep **CASCADE** | Scheduling config of the course itself. |
| 6 | `news_articles.course_date_id` → course_dates | SET NULL | — | CourseDate Delete | keep **SET NULL** | The article should survive and just lose its course link. |
| 7 | `bookings.tandem_date_id` → tandem_dates | SET NULL | B | TandemDate Delete (guarded) | **RESTRICT** | A booked customer must not lose their jump slot. |
| 8 | `course_dates.product_id` → products | **CASCADE** | B (via course) | Product Delete (guarded) | **RESTRICT** | The headline fix: deleting a product deletes every course date, and their messages and reminders. |
| 9 | `bookings.product_id` → products | SET NULL | $ B | Product Delete (guarded) | **RESTRICT** | A booking must keep what was bought. |
| 10 | `enquiries.product_id` → products | SET NULL | H | Product Delete (guarded) | **RESTRICT** | Matches the guard (`Product::deletionBlocker` counts enquiries); retire with "Active" instead. |
| 11 | `vouchers.product_id` → products | SET NULL | $ | Product Delete (guarded) | **RESTRICT** | A voucher keeps what it was sold for. |
| 12 | `product_add_ons.product_id` → products | CASCADE | — | Product Delete | keep **CASCADE** | Pure child configuration. Booked add-on prices are already captured on the booking (`price_pence`). |
| 13 | `course_dates.location_id` → locations | RESTRICT | B (via dates) | Location Delete (guarded) | keep **RESTRICT** | Already right. |
| 14 | `tandem_dates.location_id` → locations | RESTRICT | B (via dates) | Location Delete (guarded) | keep **RESTRICT** | Already right. |
| 15 | `vouchers.payment_id` → payments | SET NULL | $ | none | **RESTRICT** | Payments are never deleted; make it impossible, not just unused. |
| 16 | `payments.enquiry_id` → enquiries | SET NULL | $ H | none | **RESTRICT** | Same: a payment keeps its enquiry. |
| 17 | `bookings.enquiry_id` → enquiries | SET NULL | B H | none | **RESTRICT** | A booking keeps its provenance. |
| 18 | `enquiry_messages.enquiry_id` → enquiries | CASCADE | H | none | keep **CASCADE** | The thread *is* the enquiry, and enquiries can't be deleted (#16/#17 would refuse anyway once it has money or a booking). |
| 19 | `bookings.customer_id` → customers | SET NULL | $ B H | none (erasure anonymises) | **RESTRICT** | Erasure keeps the row (anonymised); a hard delete would orphan the booking history. |
| 20 | `enquiries.customer_id` → customers | SET NULL | H | none | **RESTRICT** | As #19. |
| 21 | `testimonials.customer_id` → customers | SET NULL | H | none | keep **SET NULL** | Erasure deletes the reviews explicitly. A review isn't money. |
| 22 | `customer_login_links.customer_id` → customers | CASCADE | — | none (erasure deletes them) | keep **CASCADE** | Pure child (single-use tokens). |
| 23 | `newsletter_campaign_recipients.newsletter_subscriber_id` → newsletter_subscribers | CASCADE | — | Subscriber Delete; **erasure** | keep **CASCADE** | Erasure must be able to delete the subscriber. A send record for an erased person shouldn't survive. |
| 24 | `newsletter_campaign_recipients.newsletter_campaign_id` → newsletter_campaigns | CASCADE | — | none | keep **CASCADE** | Campaigns can't be deleted. |
| 25 | `course_message_document.course_message_id` → course_messages | CASCADE | — | (via #4) | keep **CASCADE** | Pivot. |
| 26 | `course_message_document.document_id` → documents | CASCADE | H | Document Delete (**unguarded**) | **RESTRICT** *(Ben's call)* | Deleting a document silently removes it from the record of what was sent to students. Phase 2 would add `Document` to `GuardsDeletion` ("attached to N sent messages") so the button explains. **Alternative:** keep CASCADE if you don't need that record. |
| 27 | `discipline_instructor.discipline_id` → disciplines | CASCADE | — | Discipline Delete (guarded: page disciplines only) | keep **CASCADE** | Pivot. |
| 28 | `discipline_instructor.instructor_id` → instructors | CASCADE | — | Instructor Delete | keep **CASCADE** | Pivot. |
| 29 | `payments.created_by` → users | SET NULL | $ | none | keep **SET NULL** | If a staff account is ever removed, the payment survives with "unknown author". |
| 30 | `enquiry_messages.user_id` → users | SET NULL | H | none | keep **SET NULL** | As #29. |
| 31 | `course_messages.user_id` → users | SET NULL | H | none | keep **SET NULL** | As #29. |
| 32 | `newsletter_campaigns.user_id` → users | SET NULL | — | none | keep **SET NULL** | As #29. |

**Summary of changes proposed:** **13 FKs move to RESTRICT** (#1, 2, 3, 7, 8, 9, 10, 11, 15, 16, 17, 19,
20), **plus #26 if approved** (14). The other **18 (or 19) stay as they are**, 2 of them already RESTRICT.

## Model-level refusal

- Add a `deleting` listener (one trait, e.g. `RefusesGuardedDeletion`) on each `GuardsDeletion` model:
  Product, Booking, CourseDate, TandemDate, Location, Voucher, Discipline, plus Document if #26 is
  approved.
- When `deletionBlocker()` is non-null, it throws a domain exception carrying that message, so tinker, a
  job or a future action gets the same plain-English reason the admin shows.
- **Three layers, one rule:**
  1. the **button** explains (`guardedDelete()` disables it with the tooltip; the bulk action skips
     blocked rows);
  2. the **model** refuses any Eloquent delete;
  3. the **database** refuses even `deleteQuietly()` or a raw query.

  `GuardsDeletion` and `guardedDelete()` stay exactly as they are.
- Not every RESTRICT parent implements `GuardsDeletion` (Customer, Enquiry, Payment). They have no delete
  path, so the DB rule alone is the backstop. No listener is proposed for them.

## Phase 2 notes (for when it's approved)

- **One migration** dropping and re-adding each changed FK. Laravel's schema builder rebuilds the table on
  SQLite; it'll be proven on both drivers. Changing a rule to RESTRICT never conflicts with existing rows
  (it only affects future deletes). The pre-check the prompt asks for will still abort if any **orphaned**
  child value exists, because re-adding a constraint would fail on it.
- Tested against a seeded copy as well as a fresh DB.
- Erasure is checked by test on a customer with paid bookings (nothing on its path deletes a RESTRICT
  parent).
- A structural guard: every FK whose parent implements `GuardsDeletion` is RESTRICT or in an allowlist with
  a reason (here: #4, #5, #6, #12, #27). Proven by a planted violation.

## What Ben decides

1. Approve the table as it stands, or edit rows (especially **#26 Documents**: RESTRICT + guard, or keep
   CASCADE).
2. Approve the model-level listener on the `GuardsDeletion` models.
