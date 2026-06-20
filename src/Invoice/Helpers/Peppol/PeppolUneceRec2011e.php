<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Trait\PeppolUneceRec2011eTrait1;
use App\Invoice\Helpers\Peppol\Trait\PeppolUneceRec2011eTrait2;
use App\Invoice\Helpers\Peppol\Trait\PeppolUneceRec2011eTrait3;
use App\Invoice\Helpers\Peppol\Trait\PeppolUneceRec2011eTrait4;
use App\Invoice\Helpers\Peppol\Trait\PeppolUneceRec2011eTrait5;

class PeppolUneceRec2011e
{
    use PeppolUneceRec2011eTrait1;
    use PeppolUneceRec2011eTrait2;
    use PeppolUneceRec2011eTrait3;
    use PeppolUneceRec2011eTrait4;
    use PeppolUneceRec2011eTrait5;
        public function getUNECERec2011e(): array
    {
        return array_merge(
            $this->getUNECERec2011eChunk1(),
            $this->getUNECERec2011eChunk2(),
            $this->getUNECERec2011eChunk3(),
            $this->getUNECERec2011eChunk4(),
            $this->getUNECERec2011eChunk5(),
            $this->getUNECERec2011eChunk6(),
            $this->getUNECERec2011eChunk7(),
            $this->getUNECERec2011eChunk8(),
            $this->getUNECERec2011eChunk9(),
            $this->getUNECERec2011eChunk10(),
            $this->getUNECERec2011eChunk11(),
            $this->getUNECERec2011eChunk12(),
            $this->getUNECERec2011eChunk13(),
            $this->getUNECERec2011eChunk14(),
            $this->getUNECERec2011eChunk15(),
            $this->getUNECERec2011eChunk16(),
            $this->getUNECERec2011eChunk17(),
            $this->getUNECERec2011eChunk18(),
            $this->getUNECERec2011eChunk19(),
            $this->getUNECERec2011eChunk20(),
            $this->getUNECERec2011eChunk21(),
            $this->getUNECERec2011eChunk22(),
            $this->getUNECERec2011eChunk23(),
            $this->getUNECERec2011eChunk24(),
            $this->getUNECERec2011eChunk25(),
            $this->getUNECERec2011eChunk26(),
            $this->getUNECERec2011eChunk27(),
            $this->getUNECERec2011eChunk28(),
            $this->getUNECERec2011eChunk29(),
            $this->getUNECERec2011eChunk30(),
            $this->getUNECERec2011eChunk31(),
            $this->getUNECERec2011eChunk32(),
            $this->getUNECERec2011eChunk33(),
            $this->getUNECERec2011eChunk34(),
            $this->getUNECERec2011eChunk35(),
            $this->getUNECERec2011eChunk36(),
            $this->getUNECERec2011eChunk37(),
            $this->getUNECERec2011eChunk38(),
            $this->getUNECERec2011eChunk39(),
            $this->getUNECERec2011eChunk40(),
            $this->getUNECERec2011eChunk41(),
            $this->getUNECERec2011eChunk42(),
            $this->getUNECERec2011eChunk43(),
            $this->getUNECERec2011eChunk44(),
            $this->getUNECERec2011eChunk45(),
            $this->getUNECERec2011eChunk46(),
            $this->getUNECERec2011eChunk47(),
            $this->getUNECERec2011eChunk48(),
            $this->getUNECERec2011eChunk49(),
            $this->getUNECERec2011eChunk50(),
            $this->getUNECERec2011eChunk51(),
            $this->getUNECERec2011eChunk52(),
            $this->getUNECERec2011eChunk53(),
            $this->getUNECERec2011eChunk54(),
            $this->getUNECERec2011eChunk55(),
            $this->getUNECERec2011eChunk56(),
            $this->getUNECERec2011eChunk57(),
            $this->getUNECERec2011eChunk58(),
            $this->getUNECERec2011eChunk59(),
            $this->getUNECERec2011eChunk60(),
            $this->getUNECERec2011eChunk61(),
            $this->getUNECERec2011eChunk62(),
            $this->getUNECERec2011eChunk63(),
            $this->getUNECERec2011eChunk64(),
            $this->getUNECERec2011eChunk65(),
            $this->getUNECERec2011eChunk66(),
            $this->getUNECERec2011eChunk67(),
            $this->getUNECERec2011eChunk68(),
            $this->getUNECERec2011eChunk69(),
            $this->getUNECERec2011eChunk70(),
            $this->getUNECERec2011eChunk71(),
            $this->getUNECERec2011eChunk72(),
            $this->getUNECERec2011eChunk73(),
            $this->getUNECERec2011eChunk74(),
            $this->getUNECERec2011eChunk75(),
            $this->getUNECERec2011eChunk76(),
            $this->getUNECERec2011eChunk77(),
            $this->getUNECERec2011eChunk78(),
            $this->getUNECERec2011eChunk79(),
            $this->getUNECERec2011eChunk80(),
            $this->getUNECERec2011eChunk81(),
            $this->getUNECERec2011eChunk82(),
            $this->getUNECERec2011eChunk83(),
            $this->getUNECERec2011eChunk84(),
        );
    }
}
