<?php
declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\Inv\InvForm;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\ViewRenderer;

final class Bootstrap5ModalInv
{
    private Translator $translator;
    private ViewRenderer $viewRenderer;
    private ClientRepository $cR;
    private GroupRepository $gR;
    private SettingRepository $sR;
    private UserClientRepository $ucR;
    private InvForm $invForm;
    private array $layoutParameters;
    private array $formParameters;
    
    public function __construct(
        Translator $translator, 
        ViewRenderer $viewRenderer, 
        ClientRepository $cR, 
        GroupRepository $gR,
        SettingRepository $sR,
        UserClientRepository $ucR,    
        InvForm $invForm
    ) 
    {
        $this->translator = $translator;
        $this->viewRenderer = $viewRenderer;
        $this->cR = $cR;
        $this->gR = $gR;
        $this->sR = $sR;
        $this->ucR = $ucR;
        $this->invForm = $invForm;
        $this->layoutParameters = [];
        $this->formParameters = [];
    }
        
    public function renderPartialLayoutWithFormAsString(string $origin, array $errors) : string
    {
        $defaultGroupId = $this->sR->get_setting('default_invoice_group');
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
            'action' => ['inv/add', ['origin' => $origin]],
            'errors' => $errors,
            'form' => $this->invForm,
            'clients' => $this->cR->optionsData($this->ucR),
            'groups' => $optionsGroupData,
            'defaultGroupId' => $defaultGroupId,
            'urlKey' => Random::string(32)
        ];
        $this->layoutParameters = [
            'type' => 'inv',
            'form' => $this->viewRenderer->renderPartialAsString('/invoice/inv/modal_add_inv_form', $this->formParameters),
        ];    
        return $this->viewRenderer->renderPartialAsString('/invoice/inv/modal_layout', $this->layoutParameters);
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