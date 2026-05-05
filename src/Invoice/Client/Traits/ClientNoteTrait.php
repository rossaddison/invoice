<?php

declare(strict_types=1);

namespace App\Invoice\Client\Traits;

use App\Invoice\ClientNote\ClientNoteRepository as cnR;
use App\Invoice\ClientNote\ClientNoteService as cnS;
use App\Invoice\ClientNote\ClientNoteForm;
use App\Infrastructure\Persistence\ClientNote\ClientNote;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Json\Json;

/**
 * Trait providing AJAX note action methods for the Client domain.
 *
 * Requires the consuming class to expose:
 * @property \Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface $factory
 */
trait ClientNoteTrait
{
    /**
     * @param Request $request
     * @param cnR $cnR
     * @return Response
     */
    public function loadClientNotes(Request $request, cnR $cnR): Response
    {
        $body = $request->getQueryParams();
        /** @var int $body['client_id'] */
        $cId = $body['client_id'];
        $data = $cnR->repoClientNoteCount($cId) > 0 ?
            $cnR->repoClientquery($cId) : null;
        $parameters = [
            'success' => 1,
            'data' => $data,
        ];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cnS $cnS
     * @return Response
     */
    public function saveClientNoteNew(Request $request,
                                FormHydrator $formHydrator, cnS $cnS): Response
    {
        $body = $request->getQueryParams();
        /**
         * @var string $body['client_id']
         */
        $cId = $body['client_id'];
        $date = new \DateTimeImmutable('now');
        /**
         * @var string $body['client_note']
         */
        $note = $body['client_note'];
        $data = [
            'client_id' => $cId,
            'date' => $date->format('Y-m-d'),
            'note' => $note,
        ];
        $entity = new ClientNote();
        $form = new ClientNoteForm();
        if ($formHydrator->populateAndValidate($form, $data)) {
            $cnS->addClientNote($entity, $data);
            $parameters = [
                'success' => 1,
            ];
        } else {
            $parameters = [
                'success' => 0,
                'validation_errors' => $form->getValidationResult()
                                            ->getErrorMessagesIndexedByProperty(),
            ];
        }
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param Request $request
     * @param cnR $cnR
     * @param cnS $cnS
     * @return Response
     */
    public function deleteClientNote(Request $request, cnR $cnR, cnS $cnS): Response
    {
        $body = $request->getQueryParams();
        /**
         * @var string $body['note_id']
         */
        $note_id = $body['note_id'] ?? '';

        $clientNote = $cnR->repoClientNotequery((int) $note_id);
        //if ($clientNote) {
        $cnS->deleteClientNote($clientNote);
            return $this->factory->createResponse(Json::encode([
            'success' => 1,
        ]));
    }
}
