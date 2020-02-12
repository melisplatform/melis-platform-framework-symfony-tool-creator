<?php

namespace App\Bundle\SymfonyTpl\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="sample_table_name")
 * @ORM\Entity(repositoryClass="App\Bundle\SymfonyTpl\Repository\SampleEntityRepository")
 */
class SampleEntity
{
    //ENTITY_SETTERS_GETTERS

    /**
     * Add callback to add
     * some validations specially
     * on file type
     *
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validate(ExecutionContextInterface $context)
    {
        //FILE_FIELDS_VALIDATION
    }
}
