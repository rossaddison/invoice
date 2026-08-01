# GoCardless Direct Debit — Setup Guide (Plain English)

This app can collect payment via GoCardless Direct Debit. GoCardless's own
dashboard is not very explicit about several steps a first-time user needs —
this guide spells all of them out, based on things that actually tripped up a
real setup attempt.

## 1. Sandbox vs Live — two entirely separate accounts

GoCardless does **not** have a "test mode" toggle inside one account. Sandbox
and Live are two completely separate websites with separate signups:

- **Sandbox** (for testing, free, no approval needed):
  `manage-sandbox.gocardless.com`
- **Live** (real money, needs GoCardless's approval first):
  `manage.gocardless.com`

**Tip:** if you're testing, make sure you actually signed up at the
`-sandbox` URL. If an access token you created shows **"live"** next to it,
you're in the wrong account for testing — go create a separate account at
`manage-sandbox.gocardless.com` instead.

## 2. Do NOT create a "Partner app"

When you go looking for API access in the GoCardless dashboard, you'll be
offered two paths:

- **Access Token** ("Direct integration") — a single token tied to your own
  account. **This is what this app uses.**
- **Partner app** (OAuth) — for platforms that connect *other businesses'*
  separate GoCardless accounts (i.e. a SaaS billing platform serving many
  different merchants). This app does not do that.

**Tip:** ignore any "create an app" / "partner integration" link. Go straight
to **Developers → Create → Access Token**.

## 3. Creating the Access Token

In the sandbox (or live) dashboard: **Developers → Create → Access Token**.

- **Name**: anything you like, e.g. "Invoice App".
- **Scope**: choose **Read-write access**, not Read only. Read-only will not
  let this app create redirect flows, mandates, or schedule payments — it'll
  silently fail later if you pick the wrong one here.

**Tip:** GoCardless shows the token value **once**, immediately after you
click Create. Copy it straight away — if you navigate away without copying
it, you'll have to delete the token and create a new one.

Paste the token into this app's **Settings → Online Payment → GoCardless →
Access Token** field.

## 4. Creating the Webhook Endpoint

Still in the GoCardless dashboard: **Developers → Webhooks → Add endpoint**.

- **Name**: anything, e.g. "Invoice App webhook".
- **URL**: `https://yourdomain.com/paymentinformation/goCardlessWebhook`
  (replace with your actual domain). This exact route is already exempted
  from this app's CSRF protection, so GoCardless's server-to-server calls
  won't get rejected.
- **Secret**: leave **"Generate a secret for me"** selected — don't use a
  custom secret, and ignore the optional "Webhook client certificate"
  checkbox entirely.

**Tip:** exactly like the access token, GoCardless shows the generated
secret **once**, right after you click Create. Copy it immediately into
**Settings → Online Payment → GoCardless → Webhook Secret**.

## 5. Settings page checklist

In this app's **Settings → Online Payment** tab, under GoCardless:

- [ ] **Enabled** — ticked
- [ ] **Access Token** — pasted from step 3
- [ ] **Webhook Secret** — pasted from step 4
- [ ] **Sandbox** — ticked while testing; only untick this once you've
      switched to a real Live account's access token and webhook

## 6. Testing a payment (Sandbox)

1. Open a real invoice with an outstanding balance and choose GoCardless as
   the payment method. This redirects straight to GoCardless's own hosted
   page — there's nothing to configure for that part.
2. Fill in any test email, name, and address (use "click here to enter your
   address manually" if postcode lookup doesn't find a fake address).
3. On the bank details step, use GoCardless's documented sandbox test
   account: sort code **20-00-00**, account number **55779911** — this
   completes instantly rather than needing real bank verification.
4. You'll land back on this app's own completion page, showing
   "still processing" — this is expected. GoCardless payments are never
   instant; the invoice only actually flips to paid once GoCardless's
   webhook later confirms the collection.

**Tip:** check the GoCardless dashboard's **Payments** list to watch the
status move from `Pending submission` → `Submitted` → `Confirmed`. Once it
reaches `Confirmed`, this app's webhook should fire automatically and mark
the invoice paid — no manual action needed.

**Tip:** the charge date won't necessarily be today. GoCardless enforces its
own minimum notice period per payment scheme; this app clamps the invoice's
due date forward to at least 5 days out if it's sooner than that, rather
than letting GoCardless reject the request outright.

## 7. Known GoCardless quirks (already handled by this app, documented here
so they're not mistaken for bugs)

- **"Custom payment references are not enabled for your scheme identifier"**
  (a 422 error) — GoCardless blocks setting your own free-text reference on
  a payment unless that's separately switched on for your account by
  GoCardless support. This app doesn't attempt to set one at all — payments
  are matched back to the correct invoice internally via metadata, not the
  reference field, so this restriction doesn't affect anything.
- **"Your integration has already completed this redirect flow"** (also a
  422) — a GoCardless redirect flow (the hosted mandate-setup pages) can
  only ever be completed once. If you refresh the completion page or press
  the browser's back button and land on it again, this app now recognises
  the Direct Debit has already been scheduled and simply re-shows the
  completion page instead of trying to talk to GoCardless a second time.

## 8. If something doesn't work

- Double-check you're looking at the same environment (Sandbox vs Live) in
  both the GoCardless dashboard and this app's Sandbox checkbox — a Sandbox
  access token will not work against Live, and vice versa.
- A blank/failed webhook usually means the URL in step 4 doesn't exactly
  match this app's actual domain, or the Webhook Secret was mistyped/not
  saved.
- Check this app's own logs for `GoCardless` entries if a payment is stuck
  in a status this app doesn't seem to react to.
