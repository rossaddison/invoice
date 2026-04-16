<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

// Entities
use App\User\User;
use App\Invoice\Entity\Quote;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Setting\SettingRepository as SR;
use App\User\UserRepository as UR;
// Services
use App\Invoice\Quote\QuoteDeletionService as QDS;
// Ancillary
use Yiisoft\Security\Random;

final readonly class QuoteService
{
    public function __construct(
        private QuoteRepository $repository,
        private CR $cR,
        private GR $gR,
        private UR $uR,
        private QDS $deletionService,
    ) {
    }

    /**
     * @param array $array
     * @param Quote $model
     * @return void
     */
    private function persist(array $array, Quote $model): void
    {
        if (isset($array['client_id'])) {
            $client = $this->cR->repoClientquery((int) $array['client_id']);
            $model->setClient($client);
        }
        if (isset($array['group_id'])) {
            $group = $this->gR->repoGroupquery(
                (string) $array['group_id']
            );
            if ($group) {
                $model->setGroup($group);
            }
        }
        if (isset($array['user_id'])) {
            $user = $this->uR->findById(
                (string) $array['user_id']
            );
            if ($user) {
                $model->setUser($user);
            }
        }
    }

    /**
     * @param User $user
     * @param Quote $model
     * @param array $array
     * @param SR $s
     * @param GR $gR
     * @return Quote
     */
    public function saveQuote(
        User $user,
        Quote $model,
        array $array,
        SR $s,
        GR $gR
    ): Quote {
        $this->persist($array, $model);

        /**
         * Give a legitimate quote number to a quote that currently:
         * 1. Exists
         * 2. Has no quote number
         * 3. Has a status of 'draft'
         */
        if ((!$model->isNewRecord()) &&
            (strlen($model->getNumber() ?? '') == 0)  &&
            ($array['status_id'] == 1)) {
            $model->setNumber(
                (string) $gR->generateNumber(
                    (int) $array['group_id'],
                    true
                )
            );
        }

        $datetime_created = new \DateTimeImmutable();
        /**
         * @var string $array['date_created']
         */
        $date_created = $array['date_created'] ?? '';
        $model->setDateCreated(
            $datetime_created::createFromFormat(
                'Y-m-d',
                $date_created
            ) ?: new \DateTimeImmutable('1901/01/01')
        );

        isset($array['inv_id']) ?
            $model->setInvId((int) $array['inv_id']) : '';
        isset($array['so_id']) ?
            $model->setSoId((int) $array['so_id']) : '';
        isset($array['client_id']) ?
            $model->setClientId((int) $array['client_id']) : 0;
        isset($array['group_id']) ?
            $model->setGroupId((int) $array['group_id']) : 0;
        isset($array['status_id']) ?
            $model->setStatusId((int) $array['status_id']) : '';
        isset($array['delivery_location_id']) ?
            $model->setDeliveryLocationId(
                (int) $array['delivery_location_id']
            ) : '';
        isset($array['discount_amount']) ?
            $model->setDiscountAmount(
                (float) $array['discount_amount']
            ) : '';
        isset($array['url_key']) ?
            $model->setUrlKey((string) $array['url_key']) : '';
        isset($array['password']) ?
            $model->setPassword((string) $array['password']) : '';
        isset($array['notes']) ?
            $model->setNotes((string) $array['notes']) : '';
        if ($model->isNewRecord()) {
            $model->setInvId(0);
            $model->setSoId(0);
            // if draft quotes must get quote numbers
            if ($s->getSetting(
                'generate_quote_number_for_draft'
            ) === '1') {
                $model->setNumber(
                    (string) $gR->generateNumber(
                        (int) $array['group_id'],
                        true
                    )
                );
            } else {
                $model->setNumber('');
            }
            $model->setStatusId(1);
            $model->setUser($user);
            $model->setUserId((int) $user->getId());
            $model->setUrlKey(Random::string(32));
            $model->setDateCreated(new \DateTimeImmutable('now'));
            $model->setDateExpires($s);
            $model->setDiscountAmount(0.00);
        }
        $this->repository->save($model);
        return $model;
    }
    
    public function deleteQuote(Quote $quote): void
    {
        $this->deletionService->delete($quote);
        $this->repository->delete($quote);
    }
}
