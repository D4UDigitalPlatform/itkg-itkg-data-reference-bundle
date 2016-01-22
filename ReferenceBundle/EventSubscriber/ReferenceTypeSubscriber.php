<?php

namespace Itkg\ReferenceBundle\EventSubscriber;

use Itkg\ReferenceInterface\Repository\ReferenceTypeRepositoryInterface;
use OpenOrchestra\Backoffice\Manager\TranslationChoiceManager;
use OpenOrchestra\ModelInterface\Model\FieldTypeInterface;
use Itkg\ReferenceApiBundle\Repository\ReferenceTypeRepository;
use Symfony\Component\Form\FormEvent;
use Itkg\ReferenceInterface\Model\ReferenceTypeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ReferenceTypeSubscriber
 */
class ReferenceTypeSubscriber extends AbstractBlockReferenceTypeSubscriber
{
    protected $translationChoiceManager;
    protected $referenceTypeRepository;
    protected $contentAttributeClass;
    protected $fieldTypesConfiguration;

    /**
     * @param ReferenceTypeRepositoryInterface $referenceTypeRepository
     * @param string                           $contentAttributeClass
     * @param TranslationChoiceManager         $translationChoiceManager
     * @param array                            $fieldTypesConfiguration
     */
    public function __construct(
        ReferenceTypeRepositoryInterface $referenceTypeRepository,
        $contentAttributeClass,
        TranslationChoiceManager $translationChoiceManager,
        array $fieldTypesConfiguration
    )
    {
        $this->referenceTypeRepository = $referenceTypeRepository;
        $this->contentAttributeClass = $contentAttributeClass;
        $this->translationChoiceManager = $translationChoiceManager;
        $this->fieldTypesConfiguration = $fieldTypesConfiguration;
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $referenceType = $this->referenceTypeRepository
            ->findOneByReferenceTypeId($data->getReferenceTypeId());

        if ($referenceType instanceOf ReferenceTypeInterface) {
            foreach ($referenceType->getFields() as $referenceTypeField) {

                if (isset($this->fieldTypesConfiguration[$referenceTypeField->getType()])) {
                    $this->addFieldToForm($referenceTypeField, $form);
                }
            }
        }
    }

    /**
     * Add $contentTypeField to $form with value $fieldValue
     *
     * @param FieldTypeInterface $contentTypeField
     * @param FormInterface      $form
     */
    protected function addFieldToForm(FieldTypeInterface $contentTypeField, FormInterface $form)
    {
        $fieldTypeConfiguration = $this->fieldTypesConfiguration[$contentTypeField->getType()];

        $fieldParameters = array_merge(
            array(
                'label' => $this->translationChoiceManager->choose($contentTypeField->getLabels()),
                'mapped' => false,
            ),
            $this->getFieldOptions($contentTypeField)
        );

        if (isset($fieldParameters['required']) && $fieldParameters['required'] === true) {
            $fieldParameters['constraints'] = new NotBlank();
        }
        $form->add(
            $contentTypeField->getFieldId(),
            $fieldTypeConfiguration['type'],
            $fieldParameters
        );
    }

    /**
     * Get $contentTypeField options from conf and complete it with $contentTypeField setted values
     *
     * @param FieldTypeInterface $contentTypeField
     *
     * @return array
     */
    protected function getFieldOptions(FieldTypeInterface $contentTypeField)
    {
        $contentTypeOptions = $contentTypeField->getFormOptions();
        $options = array();
        $field = $this->fieldTypesConfiguration[$contentTypeField->getType()];
        if (isset($field['options'])) {
            $configuratedOptions = $field['options'];
            foreach ($configuratedOptions as $optionName => $optionConfiguration) {
                $options[$optionName] = (isset($contentTypeOptions[$optionName])) ? $contentTypeOptions[$optionName] : $optionConfiguration['default_value'];
            }
        }
        return $options;
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $reference = $form->getData();
        $data = $event->getData();
        $referenceType = $this->referenceTypeRepository
            ->findOneByReferenceTypeId($reference->getReferenceTypeId());

        if (is_object($referenceType)) {
            foreach ($referenceType->getFields() as $field) {
                $fieldId = $field->getFieldId();
                if ($attribute = $reference->getAttributeByName($fieldId)) {
                    $data[$fieldId] = isset($data[$fieldId]) ? $data[$fieldId] : null;
                    $attribute->setValue($this->transformData($data[$fieldId], $form->get($fieldId)));
                } elseif (is_null($attribute)) {
                    $contentAttributeClass = $this->contentAttributeClass;
                    $attribute = new $contentAttributeClass;
                    $attribute->setName($fieldId);
                    $attribute->setValue($this->transformData($data[$fieldId], $form->get($fieldId)));
                    $reference->addAttribute($attribute);
                }
            }
        }
    }
}
