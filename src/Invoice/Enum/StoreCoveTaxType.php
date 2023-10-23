<?php
declare(strict_types=1); 

namespace App\Invoice\Enum;

// Used in ..\resources\views\invoice\taxrate\__form.php
// Accessible by Settings ... Invoice Tax Rate

// Used in ..\Helpers\StoreCove\StoreCoveHelper function DocumentLevelAllowanceCharges

// Provide Storecove snake_case parameter

// https://www.storecove.com/docs/#_openapi_tax

// Note This enum format is simply used to return a snake_case parameter
// The values retrieved from here are stored as string and not as enum's. 


enum StoreCoveTaxType: string
{
    case Standard = 'standard';
    case ZeroRated = 'zero_rated';    
    case Exempt = 'exempt';
    case ReverseCharge = 'reverse_charge';
    case IntraCommunity = 'intra_community';
    case Export = 'export';
    case OutsideScope = 'outside_scope';
    case Regulation33Exempt = 'regulation33_exempt';
    case NonRegulation33Exempt = 'nonregulation33_exempt';
    case DeemedSupply = 'deemed_supply';
    case SRCAs = 'srca_s';
    case SRCAc = 'srca_c';
    case NotRegistered = 'not_registered';
    case IGST = 'igst';
    case CGST = 'cgst';
    case SGST = 'sgst';
    case CESS = 'cess';
    case STATECESS = 'state_cess';
    case SROVR = 'srovr';
    case SROVRrs = 'srovr_rs';
    case SROVRlvg = 'srovr_lvg';
    case SRLVG = 'srlvg';
}