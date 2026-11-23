<?php

namespace App\Filter\EasyAdmin;

use App\Entity\TimeSheet\Client;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;

/**
 * Filtre « Client » pour une entité qui n'a pas de client directement,
 * mais un projet qui, lui, appartient à un client (ex : Task → project → client).
 */
final class ProjectClientFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName = 'client', $label = 'Client'): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle')
            ->setFormTypeOption('value_type_options.class', Client::class)
            ->setFormTypeOption('value_type_options.choice_label', 'name')
            ->setFormTypeOption('value_type_options.attr.data-ea-widget', 'ea-autocomplete');
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $parameterName = $filterDataDto->getParameterName();
        $projectAlias = 'ea_filter_' . $parameterName . '_project';

        $queryBuilder
            ->leftJoin(\sprintf('%s.project', $alias), $projectAlias)
            ->andWhere(\sprintf('%s.client %s :%s', $projectAlias, $filterDataDto->getComparison(), $parameterName))
            ->setParameter($parameterName, $filterDataDto->getValue());
    }
}
