<?php
namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Watson\Rememberable\Query\Builder as RememberableBuilder;

interface CriteriaInterface
{
    /**
     * @param Model|DatabaseBuilder|EloquentBuilder|RememberableBuilder $model
     * @param BaseRepositoryInterface|ExtendedRepositoryInterface       $repository
     * @return mixed
     */
    public function apply($model, BaseRepositoryInterface $repository);

}
