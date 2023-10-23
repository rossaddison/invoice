<?php

declare(strict_types=1);

namespace App\Auth;

use Cycle\ORM\Select;
use Throwable;
use App\Auth\Token;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Token
 * @extends Select\Repository<TEntity>
 */
final class TokenRepository extends Select\Repository implements IdentityWithTokenRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(private EntityWriter $entityWriter, Select $select)
    {
        parent::__construct($select);
    }
    
    /**
     * 
     * @param string $token
     * @param string $type 
     * @return Identity|null
     */
    public function findIdentityByToken(string $token, string $type = null): ?Identity
    {
        $token_record =  $this->findOne(['token'=>$token, 'type' => $type]);
        return null!==$token_record ? $token_record->getIdentity() : null;        
    }
    
    /**
     * 
     * @param Token $token
     * @return void
     */
    public function save(Token $token): void
    {
        $this->entityWriter->write([$token]);
    }
    
}
