<x-filament-panels::page>
    <div class="prose max-w-3xl dark:prose-invert">
        <p class="lead">
            Everything on the public website is managed from this admin panel. Here's a
            plain-English guide to the main jobs. Each topic links to where you do it.
        </p>

        <h2>Editing page text &amp; images</h2>
        <p>
            All page wording, photos and prices live under <strong>Site content</strong> in the
            menu — one screen per page (Home, Tandem, AFF, Coached, and so on), plus
            Testimonials, Hall of Fame, Shop items, Gallery and Instructors.
        </p>
        <ul>
            <li><strong>To change a heading or paragraph:</strong> open the matching Site content
                screen, edit the text box, and press Save.</li>
            <li><strong>The "lead" line</strong> under a title is optional — clear it and that line
                simply disappears, with the spacing tidied up automatically.</li>
            <li><strong>To change a photo:</strong> use the upload box (drag a file in or click).
                Images are resized for the web automatically.</li>
        </ul>

        <h2>Products &amp; pricing</h2>
        <p>
            Under <strong>Bookings &amp; sales → Products</strong>. A product is a thing you sell
            (Tandem Skydive, AFF Course, Coaching).
        </p>
        <ul>
            <li><strong>Prices are in pounds</strong> — type <code>260.00</code>, not 26000. The
                system stores the exact pence behind the scenes.</li>
            <li><strong>AFF</strong> products also have a deposit (also in pounds).</li>
            <li><strong>Add-ons</strong> (e.g. an outside camera) are listed under the product;
                tick "purchasable" for ones customers can buy online.</li>
        </ul>

        <h2>Dates &amp; courses</h2>
        <ul>
            <li><strong>Tandem dates</strong> (Bookings &amp; sales → Tandem dates) are single days
                with a time and a number of places.</li>
            <li><strong>AFF courses</strong> (Bookings &amp; sales → AFF courses) are date ranges and
                must run for <strong>at least 5 days</strong> — the form fills in a sensible end date
                and warns you if it's too short.</li>
            <li>Both belong to a <strong>Location</strong>. A location can't have a tandem date and a
                course on the same day — the form stops you, to avoid double-booking the dropzone.</li>
            <li>You can type dates directly or pick them from the calendar.</li>
        </ul>

        <h2>Enquiries &amp; replies</h2>
        <p>
            Enquiries from the website land in <strong>Bookings &amp; sales → Enquiries</strong> (a
            number badge shows unread ones). Open one to read the thread and reply — your reply is
            emailed to the customer and kept in the thread.
        </p>

        <h2>Taking payment</h2>
        <p>From inside an enquiry you can:</p>
        <ul>
            <li><strong>Send a payment link</strong> — emails the customer a secure Stripe checkout
                link. When they pay, it becomes a booking automatically.</li>
            <li><strong>Record a bank transfer</strong> — if they paid you directly, log the amount
                (in pounds) and reference; this also creates the booking.</li>
        </ul>

        <h2>Bookings &amp; the calendar</h2>
        <p>
            Paid jumps appear under <strong>Bookings &amp; sales → Bookings</strong> and on the
            <strong>Calendar</strong>. You can reschedule a booking (the customer is emailed the new
            date) and see tandem dates and AFF courses laid out by month and location.
        </p>

        <h2>Gift vouchers</h2>
        <p>
            Customers buy vouchers online; they're emailed a code and a printable PDF. Manage and
            redeem them under <strong>Bookings &amp; sales → Vouchers</strong>.
        </p>

        <h2>Newsletter</h2>
        <p>
            Sign-ups are stored under <strong>Bookings &amp; sales → Newsletter subscribers</strong>.
            People can also opt in while booking.
        </p>

        <h2>Automated emails</h2>
        <p>
            The wording of confirmation, reminder and acknowledgement emails is editable under
            <strong>Bookings &amp; sales → Email templates</strong> — change the text, keep the
            <code>@{{ placeholders }}</code> (they're filled in with real details when the email sends).
        </p>

        <hr>
        <p class="text-sm">
            Stuck on something not covered here? The developer notes live in the project's
            <code>SETUP.md</code> and <code>DECISIONS.md</code>.
        </p>
    </div>
</x-filament-panels::page>
