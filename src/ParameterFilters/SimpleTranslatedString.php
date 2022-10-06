<?php

declare(strict_types=1);

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Czim\Filter\Enums\JoinKey;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Stringable;

/**
 * Simple string comparison for a single column on a translated attribute.
 *
 * This will assume dimsav\laravel-translatable, and assume the given table
 * must be translated to '<table name singular>_translations'. If you want to
 * override this behavior, simply pass in the full translation table name for
 * the translationTable parameter.
 *
 * Standard Laravel conventions are required for this to work, so the
 * translated table `<something>`.`id` should be referred to in the foreign key
 * on the translations table as something_translations.something_id.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @implements ParameterFilterInterface<TModel>
 */
class SimpleTranslatedString implements ParameterFilterInterface
{
    protected const TRANSLATION_TABLE_POSTFIX = '_translations';

    protected string $table;
    protected ?string $translationTable;
    protected ?string $column;
    protected string $locale;
    protected bool $exact;


    /**
     * @param string      $table
     * @param string|null $translationTable
     * @param string|null $column           if given, overrules the attribute name
     * @param string|null $locale
     * @param bool        $exact            whether this should not be a loosy (like) comparison
     */
    public function __construct(
        string $table,
        ?string $translationTable = null,
        ?string $column = null,
        ?string $locale = null,
        bool $exact = false,
    ) {
        if (empty($translationTable)) {
            $translationTable = Str::singular($table) . self::TRANSLATION_TABLE_POSTFIX;
        }

        if (empty($locale)) {
            $locale = app()->getLocale();
        }

        $this->table            = $table;
        $this->translationTable = $translationTable;
        $this->column           = $column;
        $this->locale           = $locale;
        $this->exact            = $exact;
    }

    /**
     * @param string                                 $name
     * @param string|Stringable                      $value
     * @param TModel|Builder|EloquentBuilder<TModel> $query
     * @param FilterInterface<TModel>                $filter
     * @return TModel|Builder|EloquentBuilder<TModel>
     */
    public function apply(
        string $name,
        mixed $value,
        Model|Builder|EloquentBuilder $query,
        FilterInterface $filter,
    ): Model|Builder|EloquentBuilder {
        $column = $this->translationTable . '.'
            . (! empty($this->column) ? $this->column : $name);

        $query
            ->where($this->qualifiedLocaleColumn(), $this->locale)
            ->where($column, $this->getComparisonOperator(), $this->makeValueToCompare($value));


        // Add a join for the translations, using the generic join key.
        $filter->addJoin(
            $this->joinKeyForTranslations(),
            [$this->translationTable, $this->qualifiedForeignKeyName(), '=', $this->qualifiedTableKeyName()]
        );

        return $query;
    }

    protected function getComparisonOperator(): string
    {
        if ($this->exact) {
            return '=';
        }

        return 'like';
    }

    protected function makeValueToCompare(mixed $value): string
    {
        if ($this->exact) {
            return (string) $value;
        }

        return '%' . $value . '%';
    }

    protected function qualifiedLocaleColumn(): string
    {
        return $this->translationTable . '.' . $this->localeColumnName();
    }

    protected function localeColumnName(): string
    {
        return 'locale';
    }

    protected function qualifiedTableKeyName(): string
    {
        return $this->table . '.' . $this->localKeyName();
    }

    protected function localKeyName(): string
    {
        return 'id';
    }

    protected function qualifiedForeignKeyName(): string
    {
        return $this->translationTable . '.' . $this->foreignKeyName();
    }

    protected function foreignKeyName(): string
    {
        return Str::singular($this->table) . '_id';
    }

    protected function joinKeyForTranslations(): string
    {
        return JoinKey::TRANSLATIONS;
    }
}
