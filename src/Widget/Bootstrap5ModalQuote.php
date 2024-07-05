<?php
declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\Quote\QuoteForm;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class Bootstrap5ModalQuote
{
    private Translator $translator;
    private ViewRenderer $viewRenderer;
    private ClientRepository $cR;
    private GroupRepository $gR;
    private SettingRepository $sR;
    private UserClientRepository $ucR;
    private QuoteForm $quoteForm;
    private array $layoutParameters;
    private array $formParameters;
    
    public function __construct(
        Translator $translator, 
        ViewRenderer $viewRenderer, 
        ClientRepository $cR, 
        GroupRepository $gR,
        SettingRepository $sR,
        UserClientRepository $ucR,    
        QuoteForm $quoteForm
    ) 
    {
        $this->translator = $translator;
        $this->viewRenderer = $viewRenderer;
        $this->cR = $cR;
        $this->gR = $gR;
        $this->sR = $sR;
        $this->ucR = $ucR;
        $this->quoteForm = $quoteForm;
        $this->layoutParameters = [];
        $this->formParameters = [];
    }
    
    public function renderPartialLayoutWithFormAsString(string $origin, array $errors) : string
    {
        $defaultGroupId = $this->sR->get_setting('default_quote_group');
        $optionsGroupData = [];
        $groups = $this->gR->findAllPreloaded();
        /**
         * @var \App\Invoice\Entity\Group
         */
        foreach ($groups as $group) {
            $optionsGroupData[$group->getId()] = $group->getName();
        }
        $this->formParameters = [
            'origin' => $origin,
            'title' => $this->translator->translate('i.add'),
            'action' => ['quote/add', ['origin' => $origin]],
            'errors' => $errors,
            'form' => $this->quoteForm,
            'clients' => $this->cR->optionsData($this->ucR),
            'groups' => $optionsGroupData,
            'defaultGroupId' => $defaultGroupId,
            'urlKey' => Random::string(32)
        ];
        $this->layoutParameters = [
            'type' => 'quote',
            'form' => $this->viewRenderer->renderPartialAsString('quote/modal_add_quote_form', $this->formParameters),
        ];    
        return $this->viewRenderer->renderPartialAsString('quote/modal_layout', $this->layoutParameters);
    }
    
    /**
     * @return array
     */
    public function getFormParameters() : array
    {
        /**
         * @var array $this->formParameters
         */
        return $this->formParameters;
    }       
}