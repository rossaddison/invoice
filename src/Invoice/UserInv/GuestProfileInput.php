<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Infrastructure\Persistence\UserInv\UserInv;

/**
 * What an observer's own-profile form (userinv/guest) may change.
 *
 * UserInvService::saveUserInv() was written for the administrator's form and applies whatever it is
 * given: type (administrator or not), active, all_clients and the user the row belongs to. The guest form
 * renders those as hidden inputs, which a visitor can edit or add to freely, and passed the whole
 * posted body through. An observer could therefore activate their own account, mark it administrator
 * type, ask to be assigned every client, or point the row at another user.
 *
 * The same method also clears telegram_chat_id when it is not posted. That field is no longer typed in
 * here: it is set only by the verified link (see App\Invoice\Telegram\TelegramLinkService), so it must
 * survive a save of this form unchanged.
 *
 * Everything else the form carries (name, address, contact, language, consents, ...) is the user's own to
 * edit and passes through.
 */
final class GuestProfileInput
{
    /**
     * @param array<array-key, mixed> $posted the body of the request
     *
     * @return array<array-key, mixed> the body with the protected fields replaced by the stored ones
     */
    public static function forSave(array $posted, UserInv $stored): array
    {
        $posted['user_id'] = (string) $stored->reqUserId();
        $posted['type'] = (string) ($stored->getType() ?? 1);
        $posted['active'] = true === $stored->getActive() ? '1' : '0';
        $posted['all_clients'] = true === $stored->getAllClients() ? '1' : '0';
        $posted['telegram_chat_id'] = $stored->getTelegramChatId() ?? '';
        return $posted;
    }
}
