# Telegram Payment Providers

## Overview

The Telegram settings tab (`Settings → Telegram`) includes a native Telegram payment flow powered by
[`phptg/bot-api`](https://github.com/phptg/bot-api) — a zero-dependency PHP Telegram Bot API library
developed and maintained by **[Sergei Predvoditelev (vjik)](https://github.com/vjik)**.
If you find the library useful, consider supporting Sergei via [Boosty](https://boosty.to/vjik).

Step 11 accepts a **provider token** obtained from `@BotFather → Payments`. An inline **ⓘ Providers** button opens a reference modal listing every payment provider currently supported by the Telegram Bot API.

---

## Supported Providers

| Provider | Region(s) | Notes |
|---|---|---|
| Stripe | 🇺🇳 Global | Most widely supported; test mode available |
| Payme | 🇺🇿 Uzbekistan | |
| YooMoney | 🇷🇺 Russia | Formerly Yandex.Money |
| Sberbank | 🇷🇺 Russia | |
| Tranzzo | 🇺🇦 Ukraine | |
| LiqPay | 🇺🇦 Ukraine | |
| Portmone | 🇺🇦 Ukraine | |
| Click | 🇺🇿 Uzbekistan | |
| Cryptomus | 🇺🇳 Global | Crypto payments |
| Telr | 🇦🇪 Middle East (UAE etc.) | |
| PayMaster | 🇷🇺 Russia | |
| Smartglocal | 🇷🇺 Russia / CIS | |
| ECOMMPAY | 🇺🇳 Global | |

> **Telegram Stars:** Omit the provider token entirely and set the invoice currency to `XTR` — no payment provider account is required.

---

## How to Obtain a Provider Token

1. Open the Telegram app and search for **@BotFather**.
2. Send `/mybots` and select your bot.
3. Choose **Payments**.
4. Select a provider from the list and follow the connection flow (e.g. connecting your Stripe account).
5. BotFather returns a provider token — paste it into **Settings → Telegram → Step 11**.

Further reading: [Telegram Payments documentation](https://core.telegram.org/bots/payments)

---

## Implementation

### Provider token input (`partial_settings_telegram.php`)

The provider token field is wrapped in a Bootstrap `input-group`. An anchor with `data-bs-toggle="modal"` and `href="#telegram-providers"` opens the reference modal without any JavaScript.

```php
echo H::openTag('div', ['class' => 'input-group']);
 echo H::openTag('input', [
  'type'  => 'password',
  'name'  => $providerToken,
  'id'    => $providerToken,
  'class' => 'form-control form-control-lg',
  'value' => H::encode($body[$providerToken])
 ]);
 echo H::openTag('a', [
  'href'           => '#telegram-providers',
  'class'          => 'btn btn-outline-secondary',
  'data-bs-toggle' => 'modal',
  'style'          => 'text-decoration:none',
 ]);
  echo '&#9432; Providers';
 echo H::closeTag('a');
echo H::closeTag('div');
```

### Modal HTML

The modal follows the project-wide convention: `class="modal"` (no `fade`), declared at the bottom of the partial so it is output at the same DOM level as other settings content.

```html
<div id="telegram-providers" class="modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      ...
    </div>
  </div>
</div>
```

The `initTelegramProviderPopup()` function in `src/typescript/telegram-providers.ts` runs on `DOMContentLoaded` and moves the modal element to `document.body`, ensuring it is never clipped by a tab-pane ancestor with `overflow: hidden`.

```typescript
export function initTelegramProviderPopup(): void {
    const modal = document.getElementById('telegram-providers');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
}
```

---

## Payment Method Dropdown (Step 12)

Step 12 renders a `<select>` populated from `PaymentMethodRepository::findAllPreloaded()`. The option whose name contains **Card / Direct Debit** is pre-selected when no value has been saved yet. This determines which payment method is recorded automatically when a `successful_payment` webhook fires.

The `SettingController::tabIndex()` passes the list to the partial:

```php
'telegram' => $this->webViewRenderer->renderPartialAsString($p . 'telegram', [
    'payment_methods' => $pm->findAllPreloaded(),
]),
```

---

## Files Modified / Created

| File | Change |
|---|---|
| `resources/views/invoice/setting/views/partial_settings_telegram.php` | Provider token wrapped in `input-group`; `&#9432; Providers` anchor trigger; modal HTML appended at bottom |
| `src/Invoice/Setting/SettingController.php` | `payment_methods` passed to Telegram partial |
| `src/typescript/telegram-providers.ts` | `initTelegramProviderPopup()` — moves modal to `document.body` on DOMContentLoaded |
| `src/typescript/index.ts` | Imports and calls `initTelegramProviderPopup()` |
