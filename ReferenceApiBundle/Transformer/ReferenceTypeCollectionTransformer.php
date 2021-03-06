<?php

namespace Itkg\ReferenceApiBundle\Transformer;

use Itkg\ReferenceApiBundle\Facade\ReferenceTypeCollectionFacade;
use Itkg\ReferenceInterface\Model\ReferenceInterface;
use OpenOrchestra\BaseApi\Facade\FacadeInterface;
use OpenOrchestra\BaseApi\Transformer\AbstractTransformer;

/**
 * Class ReferenceTypeCollectionTransformer
 */
class ReferenceTypeCollectionTransformer extends AbstractTransformer
{
    /**
     * @param ReferenceInterface $mixed
     *
     * @return FacadeInterface
     */
    public function transform($mixed)
    {
        $facade = new ReferenceTypeCollectionFacade();

        foreach ($mixed as $referenceType) {
            $facade->addReferenceType($this->getTransformer("reference_type")->transform($referenceType));
        }

        $facade->addLink('_self_add', $this->generateRoute(
            'itkg_reference_bundle_reference_type_new',
            array()
        ));

        return $facade;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'reference_type_collection';
    }
}
