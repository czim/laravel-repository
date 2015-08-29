<?php
namespace Czim\Repository\Test\Helpers;

use Czim\Repository\BaseRepository;

class TestBaseRepository extends BaseRepository
{
    public function model()
    {
        return TestSimpleModel::class;
    }
}
