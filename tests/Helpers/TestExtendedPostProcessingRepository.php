<?php
namespace Czim\Repository\Test\Helpers;

use Czim\Repository\ExtendedPostProcessingRepository;

class TestExtendedPostProcessingRepository extends ExtendedPostProcessingRepository
{
    // model needs an active check by default
    protected $hasActive = true;

    // test assumes cache is enabled by default
    protected $enableCache = true;


    public function model()
    {
        return TestExtendedModel::class;
    }

}

